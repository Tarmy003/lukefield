{{-- resources/views/auth/partials/toggle-script.blade.php --}}
<script>
    document.querySelectorAll('.toggle-visibility').forEach(function (button) {
        button.addEventListener('click', function () {
            var input = document.getElementById(button.dataset.target);
            var icon = button.querySelector('i');
            if (!input) { return; }
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            icon.classList.toggle('fa-eye', !show);
            icon.classList.toggle('fa-eye-slash', show);
        });
    });
</script>
