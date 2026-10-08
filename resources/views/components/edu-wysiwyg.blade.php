@props([
    'id' => 'edu-editor-' . uniqid(),
    'moduleOrder' => 1,
    'readingOrder' => 1,
])

<div 
    x-data="eduWysiwygEditor({
        value: @entangle($attributes->wire('model')),
        editorId: '{{ $id }}'
    })"
    x-init="init()"
    class="edu-wysiwyg-container"
    style="counter-reset: mod-num {{ $moduleOrder ?? 1 }} read-num {{ $readingOrder ?? 1 }} formula-idx 0;"
    wire:ignore
>
    <!-- Header with Tabs -->
    <div class="edu-wysiwyg-tabs">
        <div class="edu-tabs-group">
            <button 
                type="button" 
                class="edu-tab-btn" 
                :class="{ 'active': activeTab === 'edit' }"
                @click="switchTab('edit')"
            >
                ✏️ Editar Lectura
            </button>
            <button 
                type="button" 
                class="edu-tab-btn" 
                :class="{ 'active': activeTab === 'preview' }"
                @click="switchTab('preview')"
            >
                👁️ Vista Previa del Alumno
            </button>
        </div>
        <div style="font-size: 0.8rem; color: var(--gris); font-weight: 500;">
            <span x-show="activeTab === 'edit'">Modo Edición Visual</span>
            <span x-show="activeTab === 'preview'">Previsualización en tiempo real</span>
        </div>
    </div>

    <!-- Edit Mode Toolbar -->
    <div class="edu-wysiwyg-toolbar" x-show="activeTab === 'edit'">
        <!-- Text Styles / Headings Selector (H1 - H5) -->
        <div class="edu-toolbar-section">
            <select 
                class="edu-toolbar-select" 
                x-model="currentBlockTag" 
                @focus="saveSelection()" 
                @mousedown="saveSelection()" 
                @change="applyHeading($event.target.value)"
                title="Estilo de texto / Encabezado"
            >
                <option value="p">📄 Párrafo normal</option>
                <option value="h1">H1 — Título Principal</option>
                <option value="h2">H2 — Subtítulo Principal</option>
                <option value="h3">H3 — Encabezado de Sección</option>
                <option value="h4">H4 — Subsección</option>
                <option value="h5">H5 — Encabezado Menor</option>
            </select>
        </div>

        <!-- Inline formatting -->
        <div class="edu-toolbar-section">
            <button type="button" class="edu-toolbar-btn" @mousedown.prevent @click="format('bold')" title="Negrita">
                <strong>B</strong>
            </button>
            <button type="button" class="edu-toolbar-btn" @mousedown.prevent @click="format('italic')" title="Cursiva">
                <em>I</em>
            </button>
            <button type="button" class="edu-toolbar-btn" @mousedown.prevent @click="format('insertUnorderedList')" title="Lista con viñetas">
                • Viñetas
            </button>
            <button type="button" class="edu-toolbar-btn" @mousedown.prevent @click="format('insertOrderedList')" title="Lista numerada">
                1. Numerada
            </button>
            <button type="button" class="edu-toolbar-btn" @mousedown.prevent @click="format('insertHorizontalRule')" title="Línea horizontal">
                — Línea
            </button>
            <button type="button" class="edu-toolbar-btn" @mousedown.prevent @click="toggleTagBlock()" title="Etiqueta">
                🏷️ 
            </button>
        </div>

        <!-- Text Alignment -->
        <div class="edu-toolbar-section">
            <button type="button" class="edu-toolbar-btn" @mousedown.prevent @click="format('justifyLeft')" title="Alinear a la izquierda">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="17" y1="10" x2="3" y2="10"></line>
                    <line x1="21" y1="6" x2="3" y2="6"></line>
                    <line x1="21" y1="14" x2="3" y2="14"></line>
                    <line x1="17" y1="18" x2="3" y2="18"></line>
                </svg>
                Izquierda
            </button>
            <button type="button" class="edu-toolbar-btn" @mousedown.prevent @click="format('justifyCenter')" title="Centrar">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="10" x2="6" y2="10"></line>
                    <line x1="21" y1="6" x2="3" y2="6"></line>
                    <line x1="21" y1="14" x2="3" y2="14"></line>
                    <line x1="18" y1="18" x2="6" y2="18"></line>
                </svg>
                Centrado
            </button>
            <button type="button" class="edu-toolbar-btn" @mousedown.prevent @click="format('justifyRight')" title="Alinear a la derecha">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="21" y1="10" x2="7" y2="10"></line>
                    <line x1="21" y1="6" x2="3" y2="6"></line>
                    <line x1="21" y1="14" x2="3" y2="14"></line>
                    <line x1="21" y1="18" x2="7" y2="18"></line>
                </svg>
                Derecha
            </button>
            <button type="button" class="edu-toolbar-btn" @mousedown.prevent @click="format('justifyFull')" title="Justificar">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="21" y1="10" x2="3" y2="10"></line>
                    <line x1="21" y1="6" x2="3" y2="6"></line>
                    <line x1="21" y1="14" x2="3" y2="14"></line>
                    <line x1="21" y1="18" x2="3" y2="18"></line>
                </svg>
                Justificado
            </button>
        </div>

        <!-- Math & Media Insertion -->
        <div class="edu-toolbar-section">
            <button type="button" class="edu-toolbar-btn" @click="openFormulaModal()" style="background:#eef6ff; color:#1e40af; border-color:#bcdcff;">
                🧮 Fórmula
            </button>
            <button type="button" class="edu-toolbar-btn" @click="openImageModal()" style="background:#f0fdf4; color:#166534; border-color:#bbf7d0;">
                🖼️ Imagen
            </button>
            <button type="button" class="edu-toolbar-btn" @click="openTableModal()" style="background:#fefce8; color:#854d0e; border-color:#fef08a;">
                📊 Tabla
            </button>
            <button type="button" class="edu-toolbar-btn" @click="openCardModal()" style="background:#fefce8; color:#854d0e; border-color:#fef08a;">
                📊 Card
            </button>   
        </div>

        <!-- Educational Callout Blocks -->
        <div class="edu-toolbar-section">
            <button type="button" class="edu-toolbar-btn btn-edu-block" @click="insertEducationalBlock('definition')">
                🏷️ Definición
            </button>
            <button type="button" class="edu-toolbar-btn btn-edu-block" @click="insertEducationalBlock('important')">
                💡 Importante
            </button>
            <button type="button" class="edu-toolbar-btn btn-edu-block" @click="insertEducationalBlock('warning')">
                ⚠️ Advertencia
            </button>
            <button type="button" class="edu-toolbar-btn btn-edu-block" @click="insertEducationalBlock('example')">
                📐 Ejemplo
            </button>
            <button type="button" class="edu-toolbar-btn btn-edu-block" @click="insertEducationalBlock('note')">
                📝 Nota
            </button>
            <button type="button" class="edu-toolbar-btn btn-edu-block" @click="insertEducationalBlockNote('footnote')">
                📝 Nota al pie
            </button>
            <button type="button" class="edu-toolbar-btn btn-edu-block" @click="insertEducationalBlockCard('card')">
                🖼️ Card
            </button>

        </div>
    </div>

    <!-- Edit Mode Contenteditable Workspace -->
    <div 
        x-show="activeTab === 'edit'" 
        class="edu-wysiwyg-editable" 
        id="{{ $id }}-editable"
        contenteditable="true"
        @input="onContentChange()"
        @blur="onContentChange()"
        wire:ignore
    ></div>

    <!-- Preview Mode Viewport -->
    <div 
        x-show="activeTab === 'preview'" 
        class="edu-wysiwyg-preview-pane section-body"
        id="{{ $id }}-preview"
    >
        <div x-html="value || '<p style=\'color:var(--gris); font-style:italic;\'>No hay contenido para mostrar en la vista previa.</p>'"></div>
    </div>

    <!-- MODAL: Formula Assistant -->
    <div class="edu-modal-backdrop" x-show="showFormulaModal" style="display: none;">
        <div class="edu-modal-dialog">
            <div class="admin-modal-header">
                <h3>🧮 Asistente de Fórmulas Matemáticas</h3>
                <button type="button" class="modal-close-btn" @click="showFormulaModal = false">&times;</button>
            </div>
            <div class="admin-modal-body">
                <div class="form-group" style="margin-bottom: 10px;">
                    <label class="form-label" style="font-size: 0.9rem; margin-bottom: 4px;">Expresión Matemática (LaTeX o texto plano)</label>
                    <div class="form-hint" style="font-size: 0.78rem; margin-bottom: 6px;">Ejemplo: <code>\sigma_{max} = K_t \cdot \sigma_{nom}</code></div>
                    <textarea 
                        class="admin-textarea" 
                        x-model="formulaInput" 
                        placeholder="Escribe la fórmula aquí..."
                        rows="2"
                        style="min-height:52px; font-family: monospace; font-size: 0.92rem; margin-bottom: 0;"
                        @input="updateFormulaPreview()"
                    ></textarea>
                </div>

                <!-- Quick symbols toolbar -->
                <div style="margin-bottom:10px; display:flex; flex-wrap:wrap; align-items:center; gap:3px;">
                    <span style="font-size:0.75rem; font-weight:600; color:var(--gris); margin-right:4px;">Símbolos:</span>
                    <button type="button" class="editor-btn" style="padding: 2px 7px; font-size: 0.82rem;" @click="addSymbol('\\sigma')">σ</button>
                    <button type="button" class="editor-btn" style="padding: 2px 7px; font-size: 0.82rem;" @click="addSymbol('\\tau')">τ</button>
                    <button type="button" class="editor-btn" style="padding: 2px 7px; font-size: 0.82rem;" @click="addSymbol('\\cdot')">·</button>
                    <button type="button" class="editor-btn" style="padding: 2px 7px; font-size: 0.82rem;" @click="addSymbol('\\theta')">θ</button>
                    <button type="button" class="editor-btn" style="padding: 2px 7px; font-size: 0.82rem;" @click="addSymbol('\\pi')">π</button>
                    <button type="button" class="editor-btn" style="padding: 2px 7px; font-size: 0.82rem;" @click="addSymbol('\\frac{a}{b}')">a/b</button>
                    <button type="button" class="editor-btn" style="padding: 2px 7px; font-size: 0.82rem;" @click="addSymbol('^{2}')">x²</button>
                    <button type="button" class="editor-btn" style="padding: 2px 7px; font-size: 0.82rem;" @click="addSymbol('_{nom}')">x_nom</button>
                    <button type="button" class="editor-btn" style="padding: 2px 7px; font-size: 0.82rem;" @click="addSymbol('\\sqrt{x}')">√x</button>
                    <button type="button" class="editor-btn" style="padding: 2px 7px; font-size: 0.82rem;" @click="addSymbol('\\pm')">±</button>
                </div>

                <!-- Modalidad de Numeración (Diseño Compacto) -->
                <div class="form-group" style="background: #f8fbff; border: 1.5px solid #d5e5f8; border-radius: 12px; padding: 10px 14px; margin-bottom: 12px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <span style="font-weight: 700; color: var(--azul-oscuro); font-size: 0.86rem;">🔢 Numeración de la Fórmula:</span>
                        <span style="font-size: 0.74rem; color: var(--gris);">Estilo libro de texto</span>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 8px;">
                        <!-- Opción 1: Automática -->
                        <label 
                            style="display: flex; align-items: center; justify-content: center; gap: 6px; padding: 6px 10px; background: #ffffff; border: 2px solid; border-radius: 8px; cursor: pointer; transition: all 0.15s; user-select: none;"
                            :style="formulaNumberMode === 'auto' ? 'border-color: var(--azul-secundario); background: #eef6ff; color: var(--azul-secundario); font-weight: 700;' : 'border-color: var(--borde); color: var(--texto); font-weight: 500;'"
                        >
                            <input type="radio" name="formula_num_mode_{{ $id }}" value="auto" x-model="formulaNumberMode" style="display: none;">
                            <span style="font-size: 0.84rem;">✨ Automática</span>
                        </label>

                        <!-- Opción 2: Manual / Referencia -->
                        <label 
                            style="display: flex; align-items: center; justify-content: center; gap: 6px; padding: 6px 10px; background: #ffffff; border: 2px solid; border-radius: 8px; cursor: pointer; transition: all 0.15s; user-select: none;"
                            :style="formulaNumberMode === 'manual' ? 'border-color: var(--azul-secundario); background: #eef6ff; color: var(--azul-secundario); font-weight: 700;' : 'border-color: var(--borde); color: var(--texto); font-weight: 500;'"
                        >
                            <input type="radio" name="formula_num_mode_{{ $id }}" value="manual" x-model="formulaNumberMode" style="display: none;">
                            <span style="font-size: 0.84rem;">📌 Manual / Referencia</span>
                        </label>

                        <!-- Opción 3: Sin numeración -->
                        <label 
                            style="display: flex; align-items: center; justify-content: center; gap: 6px; padding: 6px 10px; background: #ffffff; border: 2px solid; border-radius: 8px; cursor: pointer; transition: all 0.15s; user-select: none;"
                            :style="formulaNumberMode === 'none' ? 'border-color: var(--azul-secundario); background: #eef6ff; color: var(--azul-secundario); font-weight: 700;' : 'border-color: var(--borde); color: var(--gris); font-weight: 500;'"
                        >
                            <input type="radio" name="formula_num_mode_{{ $id }}" value="none" x-model="formulaNumberMode" style="display: none;">
                            <span style="font-size: 0.84rem;">⚪ Sin número</span>
                        </label>
                    </div>

                    <!-- Input manual compacto cuando formulaNumberMode === 'manual' -->
                    <div x-show="formulaNumberMode === 'manual'" x-transition style="margin-top: 8px; padding-top: 8px; border-top: 1px dashed #cfe0f5; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px;">
                        <span style="font-size: 0.8rem; color: var(--azul-oscuro);">
                            <strong>Número a referenciar:</strong> <span style="color: var(--gris); font-size: 0.74rem;">(no incrementa el contador)</span>
                        </span>
                        <div style="display: flex; align-items: center; gap: 6px;">
                            <span style="font-weight: 700; color: var(--azul-oscuro); font-size: 1.05rem;">(</span>
                            <input 
                                type="text" 
                                class="admin-input" 
                                style="margin: 0; padding: 4px 8px; width: 90px; font-weight: 700; font-family: 'Cambria Math', 'Times New Roman', serif; text-align: center; color: var(--azul-oscuro); font-size: 0.92rem;" 
                                x-model="formulaCustomNumber" 
                                placeholder="1.1.1"
                                @input="updateFormulaPreview()"
                            >
                            <span style="font-weight: 700; color: var(--azul-oscuro); font-size: 1.05rem;">)</span>
                        </div>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" style="font-size: 0.88rem; margin-bottom: 4px;">Previsualización Visual de la Fórmula</label>
                    <div 
                        class="formula-display" 
                        id="{{ $id }}-formula-preview"
                        :class="{ 'no-number': formulaNumberMode === 'none' }"
                        :data-custom-number="formulaNumberMode === 'manual' ? ((formulaCustomNumber || '').replace(/^\s*\(+/, '').replace(/\)+\s*$/, '').trim() || '1.1.1') : null"
                        style="min-height:46px; margin: 4px 0 0; padding: 8px 75px 8px 16px;"
                    >
                        <span x-text="formulaInput ? '\\(' + formulaInput + '\\)' : 'Notación aquí...'"></span>
                    </div>
                </div>
            </div>
            <div class="admin-modal-footer">
                <button type="button" class="btn-secondary" @click="showFormulaModal = false">Cancelar</button>
                <button type="button" class="btn-primary" @click="insertFormula()">Insertar Fórmula</button>
            </div>
        </div>
    </div>

    <!-- MODAL: Image Assistant -->
    <div class="edu-modal-backdrop" x-show="showImageModal" style="display: none;">
        <div class="edu-modal-dialog-image">
            <div class="admin-modal-header">
                <h3>🖼️ Insertar Imagen Educativa</h3>
                <button type="button" class="modal-close-btn" @click="showImageModal = false">&times;</button>
            </div>
            <div class="admin-modal-body">
                <!-- Source Selector Tabs -->
                <div style="display: flex; gap: 8px; margin-bottom: 16px; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px;">
                    <button 
                        type="button" 
                        class="edu-tab-btn" 
                        :class="{ 'active': imageSourceType === 'file' }"
                        @click="imageSourceType = 'file'"
                        style="font-size: 0.85rem; padding: 6px 12px;"
                    >
                        📁 Subir desde archivo
                    </button>
                    <button 
                        type="button" 
                        class="edu-tab-btn" 
                        :class="{ 'active': imageSourceType === 'url' }"
                        @click="imageSourceType = 'url'"
                        style="font-size: 0.85rem; padding: 6px 12px;"
                    >
                        🔗 Enlace URL de Internet
                    </button>
                </div>

                <!-- Mode 1: File Upload -->
                <div x-show="imageSourceType === 'file'">
                    <div class="form-group">
                        <label class="form-label">Seleccionar Imagen de tu equipo</label>
                        <div class="form-hint">Formatos soportados: PNG, JPG, WEBP, GIF, SVG (máx. 10MB).</div>
                        
                        <div 
                            style="border: 2px dashed #cbd5e1; border-radius: 8px; padding: 22px 16px; text-align: center; background: #f8fafc; cursor: pointer; transition: all 0.2s;"
                            @click="$refs.fileInput.click()"
                        >
                            <div x-show="!isUploadingImage && !imageUrl">
                                <span style="font-size: 2.2rem; display: block; margin-bottom: 6px;">☁️</span>
                                <strong style="color: var(--azul-oscuro); font-size: 0.95rem;">Haz clic para seleccionar una imagen</strong>
                                <p style="font-size: 0.8rem; color: var(--gris); margin-top: 4px;">O elige un archivo de tu computadora</p>
                            </div>

                            <div x-show="isUploadingImage">
                                <span style="font-size: 1.8rem; display: inline-block;">⏳</span>
                                <p style="font-size: 0.9rem; color: var(--azul-oscuro); font-weight: 500; margin-top: 6px;">Cargando imagen...</p>
                            </div>

                            <div x-show="!isUploadingImage && imageUrl" style="text-align: center;">
                                <img :src="imageUrl" style="max-height: 140px; max-width: 100%; border-radius: 6px; box-shadow: 0 2px 8px rgba(0,0,0,0.12); margin: 0 auto 8px auto; display: block;">
                                <span style="font-size: 0.8rem; color: #166534; font-weight: 600; background: #dcfce7; padding: 3px 10px; border-radius: 12px; display: inline-block;">✅ Imagen lista</span>
                                <p style="font-size: 0.78rem; color: var(--gris); margin-top: 6px;">Haz clic para cambiar la imagen seleccionada</p>
                            </div>

                            <input 
                                type="file" 
                                x-ref="fileInput" 
                                accept="image/*" 
                                style="display: none;" 
                                @change="uploadImageFile($event)"
                            >
                        </div>
                        
                        <div x-show="uploadError" style="color: #b42318; font-size: 0.85rem; margin-top: 6px;" x-text="uploadError"></div>
                    </div>
                </div>

                <!-- Mode 2: URL Input -->
                <div x-show="imageSourceType === 'url'">
                    <div class="form-group">
                        <label class="form-label">URL de la Imagen o Ruta</label>
                        <div class="form-hint">Escribe la URL directa de la imagen (ej: <code>https://.../figura1.png</code>).</div>
                        <input class="admin-input" type="text" x-model="imageUrl" placeholder="https://... o /images/...">
                    </div>
                </div>

                <!-- Image Formatting Settings -->
                <div class="grid-2" style="margin-bottom:12px; margin-top: 14px;">
                    <div>
                        <label class="form-label">Alineación</label>
                        <select class="admin-select" x-model="imageAlign">
                            <option value="align-center">Centrado (Recomendado)</option>
                            <option value="align-left">Izquierda</option>
                            <option value="align-right">Derecha</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Ancho Máximo</label>
                        <select class="admin-select" x-model="imageWidth">
                            <option value="100%">100% (Ancho completo)</option>
                            <option value="75%">75% (Grande)</option>
                            <option value="50%">50% (Mediana)</option>
                            <option value="35%">35% (Pequeña)</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Texto Alternativo (Alt Text)</label>
                    <input class="admin-input" type="text" x-model="imageAlt" placeholder="Ej: Barra rectangular con filete en tensión">
                </div>

                <div class="form-group">
                    <label class="form-label">Pie de Figura (Opcional)</label>
                    <input class="admin-input" type="text" x-model="imageCaption" placeholder="Ej: Figura A-15-5. Concentrador de esfuerzos en placa muescada">
                </div>

                <div class="form-group">
                    <label class="form-label">Fuente / Referencia (Opcional)</label>
                    <input class="admin-input" type="text" x-model="imageSource" placeholder="Ej: Budynas & Nisbett (2021)">
                </div>
            </div>
            <div class="admin-modal-footer">
                <button type="button" class="btn-secondary" @click="showImageModal = false">Cancelar</button>
                <button type="button" class="btn-primary" @click="insertImage()" :disabled="!imageUrl || isUploadingImage">
                    <span x-show="!isUploadingImage">Insertar Imagen</span>
                    <span x-show="isUploadingImage">Subiendo...</span>
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL: Table Assistant -->
    <div class="edu-modal-backdrop" x-show="showTableModal" style="display: none;">
        <div class="edu-modal-dialog">
            <div class="admin-modal-header">
                <h3>📊 Insertar Tabla Educativa</h3>
                <button type="button" class="modal-close-btn" @click="showTableModal = false">&times;</button>
            </div>
            <div class="admin-modal-body">
                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Número de Filas</label>
                        <input class="admin-input" type="number" min="1" max="15" x-model="tableRows">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Número de Columnas</label>
                        <input class="admin-input" type="number" min="1" max="10" x-model="tableCols">
                    </div>
                </div>
                <div class="form-hint">La tabla insertada se podrá editar directamente de forma visual en la lectura.</div>
            </div>
            <div class="admin-modal-footer">
                <button type="button" class="btn-secondary" @click="showTableModal = false">Cancelar</button>
                <button type="button" class="btn-primary" @click="insertTable()">Insertar Tabla</button>
            </div>
        </div>
    </div>
    <!-- MODAL: Card Assistant -->
    <div class="edu-modal-backdrop" x-show="showCardModal" style="display: none;">
        <div class="edu-modal-dialog">
            <div class="admin-modal-header">
                <h3>📊 Insertar Card Educativa</h3>
                <button type="button" class="modal-close-btn" @click="showCardModal = false">&times;</button>
            </div>
            <div class="admin-modal-body">
                <div class="grid-1">
                
                    <div class="form-group">
                        <label class="form-label">Número de Columnas</label>
                        <input class="admin-input" type="number" min="1" max="4" x-model="tableCols">
                    </div>
                </div>
                <div class="form-hint">La Tarjeta insertada se podrá editar directamente de forma visual en la lectura.</div>
            </div>
            <div class="admin-modal-footer">
                <button type="button" class="btn-secondary" @click="showCardModal = false">Cancelar</button>
                <button type="button" class="btn-primary" @click="insertCard()">Insertar Card</button>
            </div>
        </div>
    </div>
</div>




<script>
if (typeof registerEduWysiwygEditor === 'function') {
    registerEduWysiwygEditor();
}
</script>
