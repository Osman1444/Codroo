// تعريف المسارات
const routes = {
    'login.php': 'login',
    'register.php': 'register',
    'logout.php': 'logout',
    'welcome.php': 'dashboard',
    'index.php': 'home'
};

// تحديث الروابط في الصفحة
document.addEventListener('DOMContentLoaded', function() {
    // تحديث الروابط
    const links = document.getElementsByTagName('a');
    for (let link of links) {
        const href = link.getAttribute('href');
        if (href && href.endsWith('.php')) {
            const newPath = href.replace('.php', '');
            link.href = newPath;
        }
    }

    // تحديث النماذج
    const forms = document.getElementsByTagName('form');
    for (let form of forms) {
        const action = form.getAttribute('action');
        if (action && action.endsWith('.php')) {
            const newAction = action.replace('.php', '');
            form.action = newAction;
        }
    }

    const resendLink = document.getElementById('resend-link');
    const countdownTimer = document.getElementById('countdown-timer');

    if (countdown > 0) {
        resendLink.style.pointerEvents = 'none';
        const interval = setInterval(() => {
            if (countdown > 0) {
                countdownTimer.textContent = `You can resend the code in ${countdown}s`;
                countdown--;
            } else {
                countdownTimer.textContent = '';
                resendLink.style.pointerEvents = 'auto';
                clearInterval(interval);
            }
        }, 1000);
    }
});

function showLogin() {
    window.location.href = 'login';
}

function showRegister() {
    window.location.href = 'register';
}

function validateRegisterForm() {
    const username = document.getElementById('regUsername').value.trim();
    const password = document.getElementById('regPassword').value;
    const email = document.getElementById('regEmail').value.trim();

    if (username === "" || password === "" || email === "") {
        alert("جميع الحقول مطلوبة");
        return false;
    }

    if (!validateEmail(email)) {
        alert("الرجاء إدخال بريد إلكتروني صحيح");
        return false;
    }

    return true;
}

function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

function validateLoginForm() {
    const username = document.getElementById('loginUsername').value.trim();
    const password = document.getElementById('loginPassword').value;

    if (username === "" || password === "") {
        alert("جميع الحقول مطلوبة");
        return false;
    }

    return true;
} 