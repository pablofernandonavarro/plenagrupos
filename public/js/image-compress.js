/**
 * Compresión de imágenes en el cliente antes de subirlas (avatares, fotos de InBody).
 * Sin dependencias — usa <canvas> nativo. Si algo falla, devuelve el archivo original
 * sin romper el flujo (la validación del backend sigue siendo el resguardo real).
 */
async function compressImageFile(file, { maxDim = 1600, quality = 0.8 } = {}) {
    if (!file || !file.type || !file.type.startsWith('image/')) {
        return file;
    }

    try {
        const bitmap = await createImageBitmap(file);
        const scale = Math.min(1, maxDim / Math.max(bitmap.width, bitmap.height));
        const canvas = document.createElement('canvas');
        canvas.width = Math.max(1, Math.round(bitmap.width * scale));
        canvas.height = Math.max(1, Math.round(bitmap.height * scale));
        canvas.getContext('2d').drawImage(bitmap, 0, 0, canvas.width, canvas.height);

        const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/jpeg', quality));

        if (!blob || blob.size >= file.size) {
            return file; // la versión comprimida no achica nada, usar el original
        }

        const name = file.name.replace(/\.[^./\\]+$/, '') + '.jpg';
        return new File([blob], name, { type: 'image/jpeg', lastModified: file.lastModified });
    } catch (e) {
        return file; // formato raro / navegador sin soporte: no romper el flujo
    }
}

/** Comprime $file y reemplaza input.files con el resultado (vía DataTransfer). Devuelve el File final. */
async function replaceInputWithCompressed(input, file, opts) {
    const compressed = await compressImageFile(file, opts);
    const dt = new DataTransfer();
    dt.items.add(compressed);
    input.files = dt.files;
    return compressed;
}

/** Comprime todos los archivos de un input múltiple y reemplaza el FileList completo. */
async function compressInputFilesInPlace(input, opts) {
    const files = Array.from(input.files || []);
    const compressed = await Promise.all(files.map(f => compressImageFile(f, opts)));
    const dt = new DataTransfer();
    compressed.forEach(f => dt.items.add(f));
    input.files = dt.files;
    return compressed;
}
