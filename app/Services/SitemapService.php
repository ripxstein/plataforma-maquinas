<?php

namespace App\Services;

/**
 * Utilidades puras (sin BD) para el mapa conceptual de módulos:
 * extracción de marcas desde el HTML de las lecturas y saneamiento
 * de los nodos/conexiones recibidos desde el diseñador.
 */
class SitemapService
{
    public const COLORS = ['blue', 'teal', 'green', 'amber', 'rose', 'violet', 'slate'];

    public const MAX_NODES = 200;

    public const MAX_EDGES = 400;

    /**
     * Extrae las marcas de concepto (<mark data-concept-id=...>) de un HTML.
     * Una misma marca puede estar fragmentada en varios <mark>; se devuelve una sola vez.
     *
     * @return array<int, array{marker_id: string, title: string}>
     */
    public static function extractMarkers(?string $html): array
    {
        if (empty($html) || stripos($html, 'data-concept-id') === false) {
            return [];
        }

        preg_match_all('/<mark\b[^>]*>/i', $html, $tags);

        $markers = [];

        foreach ($tags[0] as $tag) {
            if (! preg_match('/data-concept-id\s*=\s*"([^"]+)"/i', $tag, $idMatch)) {
                continue;
            }

            $markerId = html_entity_decode($idMatch[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');

            if (isset($markers[$markerId])) {
                continue;
            }

            $title = '';
            if (preg_match('/data-concept-title\s*=\s*"([^"]*)"/i', $tag, $titleMatch)) {
                $title = html_entity_decode($titleMatch[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }

            $markers[$markerId] = [
                'marker_id' => $markerId,
                'title' => trim($title) !== '' ? trim($title) : 'Concepto sin nombre',
            ];
        }

        return array_values($markers);
    }

    /**
     * Sanea nodos y conexiones antes de persistirlos.
     *
     * @return array{nodes: array<int, array>, edges: array<int, array>}
     */
    public static function sanitize(array $nodes, array $edges): array
    {
        $cleanNodes = [];
        $ids = [];

        foreach (array_slice($nodes, 0, self::MAX_NODES) as $node) {
            if (! is_array($node)) {
                continue;
            }

            $id = self::cleanId($node['id'] ?? null);
            if ($id === null || isset($ids[$id])) {
                continue;
            }

            $type = ($node['type'] ?? '') === 'marker' ? 'marker' : 'free';
            $markerId = $type === 'marker' ? self::cleanId($node['marker_id'] ?? null, 64) : null;

            if ($type === 'marker' && $markerId === null) {
                $type = 'free';
            }

            $label = trim(strip_tags((string) ($node['label'] ?? '')));
            $label = mb_substr($label, 0, 200);

            $color = in_array($node['color'] ?? null, self::COLORS, true)
                ? $node['color']
                : ($type === 'marker' ? 'blue' : 'teal');

            $itemId = isset($node['item_id']) && is_numeric($node['item_id']) ? (int) $node['item_id'] : null;

            $ids[$id] = true;
            $cleanNodes[] = [
                'id' => $id,
                'type' => $type,
                'label' => $label !== '' ? $label : 'Concepto',
                'x' => self::cleanCoordinate($node['x'] ?? 0),
                'y' => self::cleanCoordinate($node['y'] ?? 0),
                'color' => $color,
                'item_id' => $type === 'marker' ? $itemId : null,
                'marker_id' => $markerId,
            ];
        }

        $cleanEdges = [];
        $edgeIds = [];
        $pairs = [];

        foreach (array_slice($edges, 0, self::MAX_EDGES) as $edge) {
            if (! is_array($edge)) {
                continue;
            }

            $id = self::cleanId($edge['id'] ?? null);
            $from = self::cleanId($edge['from'] ?? null);
            $to = self::cleanId($edge['to'] ?? null);

            if ($id === null || $from === null || $to === null || $from === $to) {
                continue;
            }

            if (isset($edgeIds[$id]) || ! isset($ids[$from]) || ! isset($ids[$to])) {
                continue;
            }

            $pairKey = $from.'>'.$to;
            if (isset($pairs[$pairKey])) {
                continue;
            }

            $label = mb_substr(trim(strip_tags((string) ($edge['label'] ?? ''))), 0, 100);

            $edgeIds[$id] = true;
            $pairs[$pairKey] = true;
            $cleanEdges[] = [
                'id' => $id,
                'from' => $from,
                'to' => $to,
                'label' => $label,
            ];
        }

        return ['nodes' => $cleanNodes, 'edges' => $cleanEdges];
    }

    private static function cleanId(mixed $value, int $max = 40): ?string
    {
        if (! is_string($value) && ! is_int($value)) {
            return null;
        }

        $value = (string) $value;

        if ($value === '' || mb_strlen($value) > $max || ! preg_match('/^[A-Za-z0-9_\-]+$/', $value)) {
            return null;
        }

        return $value;
    }

    private static function cleanCoordinate(mixed $value): int
    {
        $number = is_numeric($value) ? (float) $value : 0;

        return (int) round(max(-20000, min(20000, $number)));
    }
}
