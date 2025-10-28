<?php
/**
 * Popup CSS Styles - FIXED
 * File Location: includes/popup/popup-style.css.php
 */
if (!defined('ABSPATH')) exit;
?>
<style>
.sp-popup-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.75);
    backdrop-filter: blur(4px);
    z-index: 999999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    opacity: 0;
    animation: sp-fadeIn 0.3s ease-out forwards;
}

@keyframes sp-fadeIn {
    to { opacity: 1; }
}

.sp-popup-container {
    background: #ffffff;
    border-radius: 15px;
    max-width: 500px;
    width: 100%;
    position: relative;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    transform: scale(0.9) translateY(20px);
    animation: sp-slideUp 0.4s ease-out 0.1s forwards;
}

@keyframes sp-slideUp {
    to { 
        transform: scale(1) translateY(0);
    }
}

.sp-popup-close {
    position: absolute;
    top: 15px;
    right: 15px;
    background: rgba(0, 0, 0, 0.05);
    border: none;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    font-size: 24px;
    line-height: 1;
    cursor: pointer;
    color: #666;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.sp-popup-close:hover {
    background: rgba(0, 0, 0, 0.1);
    color: #333;
    transform: rotate(90deg);
}

.sp-popup-content {
    padding: 50px 40px 40px;
    text-align: center;
}

.sp-popup-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 70px;
    height: 70px;
    background: linear-gradient(135deg, #0292B7 0%, #1AC8DB 100%);
    border-radius: 50%;
    color: white;
    margin-bottom: 20px;
    box-shadow: 0 8px 24px rgba(2, 146, 183, 0.3);
}

.sp-popup-title {
    font-family: 'Raleway', sans-serif;
    font-size: 24px;
    font-weight: 700;
    color: #1a1a1a;
    margin: 0 0 12px 0;
    line-height: 1.3;
}

.sp-popup-message {
    font-family: 'Raleway', sans-serif;
    font-size: 15px;
    color: #666;
    line-height: 1.6;
    margin: 0 0 25px 0;
}

.sp-popup-buttons {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-bottom: 20px;
}

.sp-popup-btn {
    font-family: 'Raleway', sans-serif;
    font-size: 15px;
    font-weight: 700;
    padding: 14px 28px;
    border-radius: 15px;
    cursor: pointer;
    transition: all 0.3s ease;
    border: none;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.sp-popup-btn-primary {
    background: linear-gradient(135deg, #0292B7 0%, #1AC8DB 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(2, 146, 183, 0.3);
}

.sp-popup-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(2, 146, 183, 0.4);
    text-decoration: none;
    color: white;
}

.sp-popup-btn-secondary {
    background: #f8f9fa;
    color: #666;
    border: 2px solid #e5e7eb;
}

.sp-popup-btn-secondary:hover {
    background: #ffffff;
    border-color: #cbd5e0;
    color: #333;
}

.sp-popup-footer {
    font-family: 'Raleway', sans-serif;
    font-size: 13px;
    color: #999;
    margin: 0;
}

/* Mobile Responsive */
@media (max-width: 640px) {
    .sp-popup-container {
        border-radius: 15px;
        max-width: 100%;
    }
    
    .sp-popup-content {
        padding: 40px 25px 30px;
    }
    
    .sp-popup-icon {
        width: 60px;
        height: 60px;
    }
    
    .sp-popup-icon svg {
        width: 40px;
        height: 40px;
    }
    
    .sp-popup-title {
        font-size: 20px;
    }
    
    .sp-popup-message {
        font-size: 14px;
    }
    
    .sp-popup-btn {
        padding: 12px 22px;
        font-size: 14px;
    }
}

/* Closing animation */
.sp-popup-closing {
    animation: sp-fadeOut 0.3s ease-out forwards;
}

.sp-popup-closing .sp-popup-container {
    animation: sp-slideDown 0.3s ease-out forwards;
}

@keyframes sp-fadeOut {
    to { opacity: 0; }
}

@keyframes sp-slideDown {
    to { 
        transform: scale(0.9) translateY(20px);
        opacity: 0;
    }
}
</style>