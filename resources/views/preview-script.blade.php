{{-- Injected into the admin's live preview: clicking a section (or a field marked with $section->attr()) selects it in the editor. --}}
<style>
    [data-content-section] { cursor: pointer; }
    [data-content-section]:hover { outline: 2px dashed #1f6f45; outline-offset: -2px; }
    [data-content-field]:hover { outline: 2px solid #1f6f45; outline-offset: 2px; }
</style>
<script>
    document.addEventListener('click', function (event) {
        var section = event.target.closest('[data-content-section]');
        if (!section) { return; }
        var field = event.target.closest('[data-content-field]');
        event.preventDefault();
        event.stopPropagation();
        window.parent.postMessage({
            type: 'content:select',
            section: section.getAttribute('data-content-section'),
            field: field ? field.getAttribute('data-content-field') : null
        }, '*');
    }, true);
</script>
