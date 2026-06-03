(function () {
    function uploadFile(file, type) {
        const formData = new FormData();
        formData.append('file', file);

        return fetch('/admin/ajax/tinymce-upload.php?type=' + encodeURIComponent(type), {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        }).then(async function (response) {
            const payload = await response.json().catch(function () {
                return {};
            });

            if (!response.ok || !payload.location) {
                throw new Error(payload.error || payload.message || 'Falha ao enviar arquivo');
            }

            return payload.location;
        });
    }

    function openPicker(accept, type, callback, meta) {
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = accept;
        input.style.position = 'fixed';
        input.style.left = '-9999px';
        input.style.top = '-9999px';

        input.addEventListener('change', function () {
            const file = input.files && input.files[0];
            if (!file) {
                input.remove();
                return;
            }

            uploadFile(file, type).then(function (location) {
                if (type === 'image') {
                    callback(location, { alt: file.name, title: file.name });
                } else {
                    callback(location, { title: file.name });
                }
            }).catch(function (error) {
                alert(error.message || 'Falha ao enviar arquivo');
            }).finally(function () {
                input.remove();
            });
        });

        document.body.appendChild(input);
        input.click();
    }

    window.CMSMediaTools = {
        uploadFile: uploadFile,
        openPicker: openPicker
    };
})();
