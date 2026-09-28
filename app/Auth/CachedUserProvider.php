<?php

namespace App\Auth;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Cache;

class CachedUserProvider extends EloquentUserProvider
{
    protected int $ttl = 300; // 5 minutos

    protected string $cachePrefix = 'auth_user_';

    /**
     * Recupera el usuario por su ID primario.
     * Cachea solo los atributos crudos (array) para evitar __PHP_Incomplete_Class
     * al deserializar un objeto Eloquent con el driver de cache file.
     */
    public function retrieveById($identifier): ?Authenticatable
    {
        $key = $this->cachePrefix.$identifier;

        $attributes = Cache::remember($key, $this->ttl, function () use ($identifier) {
            $user = parent::retrieveById($identifier);

            return $user?->getAttributes();
        });

        if ($attributes === null) {
            return null;
        }

        // Reconstruir el modelo desde los atributos cacheados sin query a BD
        $model = $this->createModel();
        $model->setRawAttributes($attributes, true);
        $model->exists = true;

        return $model;
    }

    /**
     * Recupera por remember-token (login "recordarme").
     * Invalida la cache por si habia un usuario anterior.
     */
    public function retrieveByToken($identifier, $token): ?Authenticatable
    {
        Cache::forget($this->cachePrefix.$identifier);

        return parent::retrieveByToken($identifier, $token);
    }

    /**
     * Valida las credenciales (formulario login).
     */
    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        return parent::retrieveByCredentials($credentials);
    }

    /**
     * Invalida la cache del usuario cuando se actualiza el remember token.
     */
    public function updateRememberToken(Authenticatable $user, $token): void
    {
        parent::updateRememberToken($user, $token);
        Cache::forget($this->cachePrefix.$user->getAuthIdentifier());
    }

    /**
     * Invalida explicitamente el cache del usuario (logout, cambio de perfil).
     */
    public function forgetCached(int|string $userId): void
    {
        Cache::forget($this->cachePrefix.$userId);
    }
}
