document.addEventListener('DOMContentLoaded', function() {
    // Toggle password visibility
    const togglePassword = document.querySelector('.toggle-password');
    if (togglePassword) {
        togglePassword.addEventListener('click', function() {
            const passwordInput = document.querySelector(this.getAttribute('toggle'));
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    }

    // User type selection effects
    const userTypeSelect = document.getElementById('user_type');
    if (userTypeSelect) {
        userTypeSelect.addEventListener('change', function() {
            const studentFields = document.querySelectorAll('.student-field');
            const teacherFields = document.querySelectorAll('.teacher-field');
            
            if (this.value === 'student') {
                studentFields.forEach(field => field.style.display = 'block');
                teacherFields.forEach(field => field.style.display = 'none');
            } else if (this.value === 'teacher') {
                studentFields.forEach(field => field.style.display = 'none');
                teacherFields.forEach(field => field.style.display = 'block');
            } else {
                studentFields.forEach(field => field.style.display = 'none');
                teacherFields.forEach(field => field.style.display = 'none');
            }
        });
    }

    // Form validation
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            let isValid = true;
            const requiredFields = form.querySelectorAll('[required]');
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('error');
                } else {
                    field.classList.remove('error');
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('يرجى ملء جميع الحقول المطلوبة');
            }
        });
    });
});