<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$currentRole = $_SESSION['role'] ?? 'parent';
$profileUrl = 'profile.php';
$logoutUrl = '../logout.php';

if ($currentRole === 'hospital') {
    $dropdownName = $adminName ?? $_SESSION['name'] ?? 'Hospital Admin';
    $dropdownAvatar = 'fa-solid fa-hospital-user';
} elseif ($currentRole === 'admin') {
    $dropdownName = $adminName ?? $_SESSION['name'] ?? 'Admin';
    $dropdownAvatar = 'fa-solid fa-user-shield';
} else {
    $dropdownName = $parentName ?? $_SESSION['name'] ?? 'User';
    $dropdownAvatar = 'fa-solid fa-user';
}
?>
<!-- User Profile Dropdown Component -->
<div class="user-dropdown-wrapper">
    <button type="button" class="user-profile-btn" id="userMenuToggleBtn" aria-haspopup="true" aria-expanded="false" title="Account Menu">
        <div class="avatar-circle">
            <i class="<?= $dropdownAvatar ?>"></i>
        </div>
        <span class="user-pill-name">
            <?= htmlspecialchars((string)$dropdownName, ENT_QUOTES, 'UTF-8') ?>
            <i class="fa-solid fa-caret-down caret-icon"></i>
        </span>
    </button>
    <div class="user-dropdown-menu" id="userDropdownContent">
        <a href="<?= $profileUrl ?>" class="dropdown-menu-item">
            <i class="fa-regular fa-user"></i>
            <span>Profile</span>
        </a>
        <div class="dropdown-menu-divider"></div>
        <a href="<?= $logoutUrl ?>" class="dropdown-menu-item logout-link">
            <i class="fa-solid fa-arrow-right-from-bracket"></i>
            <span>Log Out</span>
        </a>
    </div>
</div>

<style>
/* Dashboard User Dropdown Styles */
.user-dropdown-wrapper {
    position: relative;
    display: inline-block;
}

.user-profile-btn {
    display: flex;
    align-items: center;
    gap: 10px;
    color: var(--dark, #102b46);
    font-size: 13.5px;
    font-weight: 600;
    cursor: pointer;
    background: #ffffff;
    border: 1px solid #dce8ec;
    padding: 4px 14px 4px 5px;
    border-radius: 30px;
    transition: all 0.2s ease;
    user-select: none;
    font-family: inherit;
    outline: none;
}

.user-profile-btn:hover,
.user-profile-btn.is-active {
    background: #eaf8f8;
    border-color: #c7e3e7;
}

.user-profile-btn .avatar-circle {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: linear-gradient(135deg, #087e8b, #13a6a6);
    color: white;
    display: grid;
    place-items: center;
    font-size: 14px;
    flex-shrink: 0;
}

.user-profile-btn .user-pill-name {
    display: flex;
    align-items: center;
    gap: 6px;
    color: #102b46;
}

.user-profile-btn .caret-icon {
    font-size: 10px;
    color: #7b8e9e;
    transition: transform 0.2s ease;
}

.user-profile-btn.is-active .caret-icon {
    transform: rotate(180deg);
}

.user-dropdown-menu {
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    background: #ffffff;
    min-width: 175px;
    border-radius: 14px;
    box-shadow: 0 12px 32px rgba(16, 43, 70, 0.14), 0 2px 6px rgba(16, 43, 70, 0.04);
    border: 1px solid #e1e9ee;
    padding: 6px;
    z-index: 9999;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-8px) scale(0.97);
    transition: opacity 0.2s cubic-bezier(0.16, 1, 0.3, 1), transform 0.2s cubic-bezier(0.16, 1, 0.3, 1), visibility 0.2s;
    pointer-events: none;
}

.user-dropdown-menu.is-open {
    opacity: 1;
    visibility: visible;
    transform: translateY(0) scale(1);
    pointer-events: auto;
}

.dropdown-menu-item {
    display: flex;
    align-items: center;
    gap: 11px;
    padding: 10px 14px;
    color: #102b46;
    font-size: 13.5px;
    font-weight: 500;
    text-decoration: none;
    border-radius: 9px;
    transition: background 0.15s ease, color 0.15s ease;
}

.dropdown-menu-item i {
    font-size: 14px;
    color: #087e8b;
    width: 18px;
    text-align: center;
    transition: transform 0.15s ease;
}

.dropdown-menu-item:hover {
    background: #f0f7f8;
    color: #087e8b;
}

.dropdown-menu-item:hover i {
    transform: scale(1.1);
}

.dropdown-menu-divider {
    height: 1px;
    background: #edf2f5;
    margin: 4px 6px;
}

.dropdown-menu-item.logout-link {
    color: #dc2626;
}

.dropdown-menu-item.logout-link i {
    color: #dc2626;
}

.dropdown-menu-item.logout-link:hover {
    background: #fef2f2;
    color: #b91c1c;
}
</style>

<script>
(function() {
    function initUserDropdown() {
        var toggle = document.getElementById("userMenuToggleBtn");
        var menu = document.getElementById("userDropdownContent");
        if (!toggle || !menu) return;

        // Prevent attaching duplicate listeners
        if (toggle.dataset.dropdownBound === "true") return;
        toggle.dataset.dropdownBound = "true";

        toggle.addEventListener("click", function(e) {
            e.stopPropagation();
            var isOpen = menu.classList.contains("is-open");
            if (isOpen) {
                closeDropdown();
            } else {
                openDropdown();
            }
        });

        function openDropdown() {
            menu.classList.add("is-open");
            toggle.classList.add("is-active");
            toggle.setAttribute("aria-expanded", "true");
        }

        function closeDropdown() {
            menu.classList.remove("is-open");
            toggle.classList.remove("is-active");
            toggle.setAttribute("aria-expanded", "false");
        }

        document.addEventListener("click", function(e) {
            if (!toggle.contains(e.target) && !menu.contains(e.target)) {
                closeDropdown();
            }
        });

        document.addEventListener("keydown", function(e) {
            if (e.key === "Escape" && menu.classList.contains("is-open")) {
                closeDropdown();
                toggle.focus();
            }
        });
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initUserDropdown);
    } else {
        initUserDropdown();
    }
})();
</script>
