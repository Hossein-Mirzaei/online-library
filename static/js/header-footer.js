(function() {
    'use strict';
    
    const hamburger = document.getElementById('hamburgerBtn');
    const drawer = document.getElementById('drawerMenu');
    const overlay = document.getElementById('drawerOverlay');
    const closeBtn = document.getElementById('drawerClose');
    
    function openDrawer() {
        drawer?.classList.add('show');
        overlay?.classList.add('show');
        document.body.style.overflow = 'hidden';
        
        const spans = hamburger?.querySelectorAll('span');
        if (spans) {
            spans[0].style.transform = 'rotate(45deg) translate(5px, 5px)';
            spans[1].style.opacity = '0';
            spans[2].style.transform = 'rotate(-45deg) translate(5px, -5px)';
        }
    }
    
    function closeDrawer() {
        drawer?.classList.remove('show');
        overlay?.classList.remove('show');
        document.body.style.overflow = '';
        
        const spans = hamburger?.querySelectorAll('span');
        if (spans) {
            spans[0].style.transform = '';
            spans[1].style.opacity = '';
            spans[2].style.transform = '';
        }
    }
    
    hamburger?.addEventListener('click', openDrawer);
    closeBtn?.addEventListener('click', closeDrawer);
    overlay?.addEventListener('click', closeDrawer);
    
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && drawer?.classList.contains('show')) {
            closeDrawer();
        }
    });
    
    drawer?.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', closeDrawer);
    });
    
    const scrollBtn = document.getElementById('scrollToTop');
    if (scrollBtn) {
        window.addEventListener('scroll', () => {
            scrollBtn.classList.toggle('visible', window.scrollY > 200);
        });
        scrollBtn.addEventListener('click', (e) => {
            e.preventDefault();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }
})();