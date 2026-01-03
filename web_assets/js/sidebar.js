
$(document).ready(function () {
    // Menu toggle functionality
    $('#menuToggle').click(function () {
        if (window.innerWidth <= 768) {
            $('#sidebar').toggleClass('mobile-active');
            $('#overlay').toggleClass('active');
        } else {
            $('#sidebar').toggleClass('collapsed');
            $('#mainContent').toggleClass('shifted');

            // Close all submenus when sidebar is collapsed
            if ($('#sidebar').hasClass('collapsed')) {
                $('.submenu').removeClass('open').addClass('collapsed');
                $('.menu-arrow').removeClass('rotated');
            }
        }
    });

    // Overlay click to close sidebar
    $('#overlay').click(function () {
        $('#sidebar').removeClass('mobile-active');
        $('#overlay').removeClass('active');
        closeDropdown();
    });

    // Handle menu item clicks - FIXED VERSION
    $('[data-navigation="true"]').click(function () {
        // Sirf navigation items par sidebar close karo mobile me
        if (window.innerWidth <= 768) {
            $('#sidebar').removeClass('mobile-active');
            $('#overlay').removeClass('active');
        }
    });

    // Handle submenu toggles - SEPARATE HANDLER
    $('[data-toggle="submenu"]').click(function(e) {
        e.preventDefault();
        e.stopPropagation();
        toggleSubmenu(this);
    });

    // Close dropdown when clicking outside
    $(document).click(function(event) {
        if (!$(event.target).closest('.user-info').length) {
            closeDropdown();
        }
    });
});

function toggleSubmenu(element) {
    // Don't open submenu if sidebar is collapsed
    if ($('#sidebar').hasClass('collapsed')) {
        return;
    }

    const submenu = $(element).next('.submenu');
    const arrow = $(element).find('.menu-arrow');

    // Check if current submenu is already open
    const isCurrentlyOpen = submenu.hasClass('open');

    // Close all other submenus first
    $('.submenu').not(submenu).removeClass('open').addClass('collapsed');
    $('.menu-arrow').not(arrow).removeClass('rotated');

    // Toggle current submenu based on its previous state
    if (isCurrentlyOpen) {
        submenu.removeClass('open').addClass('collapsed');
        arrow.removeClass('rotated');
    } else {
        submenu.removeClass('collapsed').addClass('open');
        arrow.addClass('rotated');
    }
}

function navigateToPage(page) {
    toastr.info(`Navigating to ${page}...`);
    setTimeout(() => {
        toastr.success(`${page} page would load here!`);
    }, 1000);
}

function toggleDropdown() {
    const dropdown = document.getElementById('userDropdown');
    dropdown.classList.toggle('show');
}

function closeDropdown() {
    const dropdown = document.getElementById('userDropdown');
    dropdown.classList.remove('show');
}

function openProfile() {
    closeDropdown();
    toastr.info('Redirecting to profile page...');
    setTimeout(() => {
        toastr.success('Profile page would open here!');
    }, 1000);
}

function showSection(section) {
    closeDropdown();
    $('.menu-item').removeClass('active');
    if (event && event.target && event.target.closest('.menu-item')) {
        event.target.closest('.menu-item').classList.add('active');
    }
    toastr.info(`Opening ${section} section...`);
}

