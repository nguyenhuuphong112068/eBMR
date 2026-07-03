
// --- Typography Normalization ---
window.openTypographySettings = function() {
    let settings = {
        h1: '16pt',
        h2: '14pt',
        normal: '12pt'
    };
    
    // Check if we already have global settings saved
    const settingsBlock = window.items.find(i => i.type === 'document-settings');
    if (settingsBlock && settingsBlock.typography) {
        settings = Object.assign(settings, settingsBlock.typography);
    }
    
    $('#typo-h1').val(settings.h1);
    $('#typo-h2').val(settings.h2);
    $('#typo-normal').val(settings.normal);
    
    $('#typographySettingsModal').modal('show');
};

window.applyTypographySettings = function() {
    const settings = {
        h1: $('#typo-h1').val() || '16pt',
        h2: $('#typo-h2').val() || '14pt',
        normal: $('#typo-normal').val() || '12pt'
    };
    
    // Save to window.items
    let settingsBlock = window.items.find(i => i.type === 'document-settings');
    if (!settingsBlock) {
        settingsBlock = {
            id: 'blk_settings_' + Date.now(),
            type: 'document-settings',
            typography: settings,
            section_id: window.catId // Bind to root
        };
        window.items.push(settingsBlock);
    } else {
        settingsBlock.typography = settings;
    }
    
    window.injectTypographyStyles(settings);
    
    $('#typographySettingsModal').modal('hide');
    window.markAsUnsaved();
    
    Swal.fire({
        icon: 'success',
        title: 'Thành công',
        text: 'Cỡ chữ đã được chuẩn hóa và áp dụng cho toàn bộ hồ sơ!',
        timer: 2000,
        showConfirmButton: false
    });
};

window.injectTypographyStyles = function(settings) {
    if (!settings) {
        const settingsBlock = window.items ? window.items.find(i => i.type === 'document-settings') : null;
        if (settingsBlock && settingsBlock.typography) {
            settings = settingsBlock.typography;
        } else {
            return; // No custom settings
        }
    }
    
    let styleTag = document.getElementById('ebmr-typography-style');
    if (!styleTag) {
        styleTag = document.createElement('style');
        styleTag.id = 'ebmr-typography-style';
        document.head.appendChild(styleTag);
    }
    
    // Create CSS string with !important to override inline styles
    const css = `
        /* Typography overrides for Designer and Rendered View */
        .ebmr-content-wrapper h1, 
        .ebmr-content-wrapper h1 *,
        .ebmr-designer-wrapper h1,
        .ebmr-designer-wrapper h1 * { 
            font-size: ${settings.h1} !important; 
        }
        
        .ebmr-content-wrapper h2, 
        .ebmr-content-wrapper h2 *,
        .ebmr-designer-wrapper h2,
        .ebmr-designer-wrapper h2 * { 
            font-size: ${settings.h2} !important; 
        }
        
        /* Normal text overrides: paragraphs, spans, table cells */
        .ebmr-content-wrapper p, 
        .ebmr-content-wrapper p *,
        .ebmr-content-wrapper span,
        .ebmr-content-wrapper td,
        .ebmr-content-wrapper td *,
        .ebmr-content-wrapper div:not(.section-header),
        .ebmr-designer-wrapper p,
        .ebmr-designer-wrapper p *,
        .ebmr-designer-wrapper span,
        .ebmr-designer-wrapper td,
        .ebmr-designer-wrapper td *,
        .ebmr-designer-wrapper div.static-text-display,
        .ebmr-designer-wrapper div.static-text-display * { 
            /* Only override if they don't explicitly belong to a header */
            font-size: ${settings.normal} !important; 
        }
        
        /* But keep section headers at their own size (usually 14pt or 16pt) */
        .ebmr-content-wrapper .section-header,
        .ebmr-designer-wrapper .section-header-display {
            font-size: ${settings.h1} !important;
        }
    `;
    
    styleTag.innerHTML = css;
};

// Auto-inject on load
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(() => {
        if (window.items) {
            window.injectTypographyStyles();
        }
    }, 500);
});
