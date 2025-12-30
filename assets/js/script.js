// Print Result Function
function printResult() {
  window.print();
}

// Form Validation Enhancement
document.addEventListener('DOMContentLoaded', function() {
  // Add validation feedback
  const forms = document.querySelectorAll('form[novalidate]');
  
  forms.forEach(form => {
    form.addEventListener('submit', function(event) {
      if (!form.checkValidity()) {
        event.preventDefault();
        event.stopPropagation();
      }
      form.classList.add('was-validated');
    });
  });
  
  // Auto-uppercase USN fields
  const usnInputs = document.querySelectorAll('input[name="usn"]');
  usnInputs.forEach(input => {
    input.addEventListener('input', function() {
      this.value = this.value.toUpperCase();
    });
  });
});

// Session timeout warning (optional)
let sessionTimeout;
function resetSessionTimer() {
  clearTimeout(sessionTimeout);
  sessionTimeout = setTimeout(() => {
    alert('Your session will expire soon due to inactivity. Please save your work.');
  }, 25 * 60 * 1000); // 25 minutes warning (5 min before 30 min timeout)
}

// Reset timer on user activity
['mousedown', 'keypress', 'scroll', 'touchstart'].forEach(event => {
  document.addEventListener(event, resetSessionTimer, true);
});

resetSessionTimer();
  
