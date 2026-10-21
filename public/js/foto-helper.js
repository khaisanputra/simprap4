/* =========================================================
   Shared Foto Helper — Resize & compress client-side
   Digunakan oleh Absen Masuk, Absen Pulang, Laporan Teknisi, Laporan Sales
   - Max 1600x1600 keep aspect, JPEG quality 0.82, single compression
   - Validasi Blob/File, DataTransfer inject
   ========================================================= */
window.FotoHelper = (function(){
    var MAX_DIM = 1600;
    var QUALITY = 0.82;

    function hitungDimensiBaru(w, h, max){
        max = max || MAX_DIM;
        if (w <= max && h <= max) return {w:w, h:h};
        var scale = Math.min(max / w, max / h);
        return {w: Math.max(1, Math.round(w * scale)), h: Math.max(1, Math.round(h * scale))};
    }

    function isImageFile(file){
        if (!file) return false;
        var t = (file.type || '').toLowerCase();
        if (t.indexOf('image/') === 0) return true;
        var ext = (file.name || '').split('.').pop().toLowerCase();
        return ['jpg','jpeg','png','webp'].indexOf(ext) !== -1;
    }

    function compressImageFile(file, callback){
        if (!file || !isImageFile(file)){
            callback(null, 'File harus berupa gambar.');
            return;
        }
        var url;
        try { url = URL.createObjectURL(file); } catch(e){ callback(null, 'Gagal membaca file.'); return; }
        var img = new Image();
        img.onload = function(){
            try {
                var dim = hitungDimensiBaru(img.naturalWidth || img.width, img.naturalHeight || img.height, MAX_DIM);
                var canvas = document.createElement('canvas');
                canvas.width = dim.w;
                canvas.height = dim.h;
                var ctx = canvas.getContext('2d');
                if (!ctx){
                    URL.revokeObjectURL(url);
                    callback(null, 'Gagal memproses foto (canvas tidak didukung).');
                    return;
                }
                ctx.fillStyle = '#FFFFFF';
                ctx.fillRect(0,0,dim.w,dim.h);
                ctx.drawImage(img,0,0,dim.w,dim.h);
                URL.revokeObjectURL(url);
                if (!canvas.toBlob){
                    try {
                        var dataUrlFallback = canvas.toDataURL('image/jpeg', QUALITY);
                        var bin = atob(dataUrlFallback.split(',')[1]);
                        var arr = new Uint8Array(bin.length);
                        for (var i=0;i<bin.length;i++) arr[i]=bin.charCodeAt(i);
                        var blobFallback = new Blob([arr], {type:'image/jpeg'});
                        if (!blobFallback || blobFallback.size===0){ callback(null, 'Gagal memproses foto (blob kosong).'); return; }
                        var fileOut = new File([blobFallback], 'attendance.jpg', {type:'image/jpeg'});
                        callback(fileOut, null);
                    } catch(e){ callback(null, 'Gagal kompresi foto.'); }
                    return;
                }
                canvas.toBlob(function(blob){
                    if (!blob){ callback(null, 'Gagal memproses foto (blob kosong).'); return; }
                    if (blob.size===0){ callback(null, 'Hasil foto kosong.'); return; }
                    try {
                        var fileOut2 = new File([blob], 'attendance.jpg', {type:'image/jpeg'});
                        if (!fileOut2 || fileOut2.size===0){ callback(null, 'Gagal membuat file foto.'); return; }
                        callback(fileOut2, null);
                    } catch(e){ callback(null, 'Gagal membuat file foto.'); }
                }, 'image/jpeg', QUALITY);
            } catch(e){
                try { URL.revokeObjectURL(url); } catch(err){}
                callback(null, 'Gagal memproses foto.');
            }
        };
        img.onerror = function(){
            try { URL.revokeObjectURL(url); } catch(e){}
            callback(null, 'File gambar tidak dapat dibaca atau rusak.');
        };
        img.src = url;
    }

    function compressMultipleFiles(files, finalCb){
        // files: array File, callback(newFilesArray, errorMsg)
        var result = [];
        var i = 0;
        function next(){
            if (i >= files.length){ finalCb(result, null); return; }
            var f = files[i++];
            compressImageFile(f, function(out, err){
                if (err || !out){ finalCb(null, err || 'Gagal memproses foto.'); return; }
                // preserve original name prefix? use attendance-N.jpg for laporan sorting
                try {
                    var named = new File([out], 'attendance-'+result.length+'.jpg', {type:'image/jpeg'});
                    result.push(named);
                } catch(e){ result.push(out); }
                next();
            });
        }
        next();
    }

    function injectFilesToInput(inputEl, files){
        if (!inputEl) return false;
        try {
            if (typeof DataTransfer === 'undefined') return false;
            var dt = new DataTransfer();
            for (var k=0;k<files.length;k++) dt.items.add(files[k]);
            if (!dt.files || dt.files.length===0) return false;
            inputEl.files = dt.files;
            if (!inputEl.files || inputEl.files.length===0) return false;
            // verify each not empty
            for (var j=0;j<inputEl.files.length;j++) if (inputEl.files[j].size===0) return false;
            return true;
        } catch(e){ return false; }
    }

    function canvasToJpegFile(canvas, callback){
        if (!canvas || !canvas.toBlob){ callback(null, 'Browser tidak mendukung kompresi.'); return; }
        canvas.toBlob(function(blob){
            if (!blob || blob.size===0){ callback(null, 'Gagal memproses foto (blob kosong).'); return; }
            try {
                var file = new File([blob], 'attendance.jpg', {type:'image/jpeg'});
                callback(file, null);
            } catch(e){ callback(null, 'Gagal membuat file foto.'); }
        }, 'image/jpeg', QUALITY);
    }

    return {
        MAX_DIM: MAX_DIM,
        QUALITY: QUALITY,
        hitungDimensiBaru: hitungDimensiBaru,
        isImageFile: isImageFile,
        compressImageFile: compressImageFile,
        compressMultipleFiles: compressMultipleFiles,
        injectFilesToInput: injectFilesToInput,
        canvasToJpegFile: canvasToJpegFile
    };
})();
