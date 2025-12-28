// ChoicePoint - Интерактивный JavaScript

document.addEventListener('DOMContentLoaded', function() {
    console.log("ChoicePoint фронтенд готов");

    // Плавное закрытие алертов
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        const closeBtn = document.createElement('button');
        closeBtn.innerHTML = '✕';
        closeBtn.className = 'alert-close';
        closeBtn.style.cssText = `
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: inherit;
            cursor: pointer;
            font-size: 1.2rem;
            padding: 0;
            opacity: 0.7;
            transition: opacity 0.2s;
        `;
        closeBtn.onmouseover = () => closeBtn.style.opacity = '1';
        closeBtn.onmouseout = () => closeBtn.style.opacity = '0.7';
        
        alert.style.position = 'relative';
        alert.appendChild(closeBtn);
        
        closeBtn.addEventListener('click', function() {
            alert.style.animation = 'slideOut 0.3s ease forwards';
            setTimeout(() => alert.remove(), 300);
        });
    });

    // Плавная анимация при скролле
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.animation = 'slideIn 0.5s ease forwards';
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('.question-card').forEach(card => {
        observer.observe(card);
    });

    // Анимация прогресс-бара
    const progressBars = document.querySelectorAll('.progress-fill');
    progressBars.forEach(bar => {
        const width = bar.style.width;
        bar.style.width = '0';
        setTimeout(() => {
            bar.style.width = width;
        }, 100);
    });

    // Улучшение UX для мобильных
    if (window.innerWidth < 768) {
        document.querySelectorAll('button').forEach(btn => {
            btn.style.minHeight = '44px'; // Лучше для тача
        });
    }
});

// Стиль для слайд-аута
const style = document.createElement('style');
style.textContent = `
    @keyframes slideOut {
        to {
            opacity: 0;
            transform: translateY(-10px);
        }
    }
`;
document.head.appendChild(style);
