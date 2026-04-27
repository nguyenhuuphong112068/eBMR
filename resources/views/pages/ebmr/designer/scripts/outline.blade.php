<script>
    function toRoman(num) {
        const roman = { M: 1000, CM: 900, D: 500, CD: 400, C: 100, XC: 90, L: 50, XL: 40, X: 10, IX: 9, V: 5, IV: 4, I: 1 };
        let str = '';
        for (let i in roman) {
            while (num >= roman[i]) {
                str += i;
                num -= roman[i];
            }
        }
        return str;
    }

    function buildOutline() {
        const outlineContainer = document.getElementById('document-outline');
        if (!outlineContainer) return;

        let seenSections = new Set();
        let sectionIndex = 0;
        let counters = [0, 0, 0, 0]; // For H1, H2, H3, H4
        let html = '';

        items.forEach((item, idx) => {
            const sid = item.section_id || '';
            const parts = sid.split('_');
            const code = parts.length > 1 ? parts[parts.length - 1] : null;

            if (item.type === 'section') {
                seenSections.add(sid);
                sectionIndex++;
                counters = [0, 0, 0, 0]; // Reset counters for new section
                const romanNum = toRoman(sectionIndex);
                html += `
                    <div class="outline-item outline-section p-2 mb-1 rounded cursor-pointer transition-all" 
                         data-target-id="${item.id}"
                         onclick="scrollToBlock('${item.id}')" 
                         style="background: rgba(14, 165, 233, 0.05); border-left: 3px solid #0ea5e9;">
                        <div class="d-flex align-items-center">
                            <span class="outline-index fw-bold text-primary me-2" style="font-size: 0.8rem;">${romanNum}.</span>
                            <span class="outline-label fw-bold text-navy text-truncate" style="font-size: 0.85rem;" title="${item.label}">${item.label || 'Chưa đặt tên'}</span>
                        </div>
                    </div>
                `;
            } else if (code !== null && !seenSections.has(sid)) {
                // Virtual section for Section 0 or 9 if they don't have a section block
                if (code === '0' || code === '9') {
                    seenSections.add(sid);
                    sectionIndex++;
                    counters = [0, 0, 0, 0];
                    const romanNum = toRoman(sectionIndex);
                    const virtualLabel = code === '0' ? 'Thông tin chung' : 'Hồ sơ lưu/Kết thúc';
                    html += `
                        <div class="outline-item outline-section p-2 mb-1 rounded cursor-pointer transition-all" 
                             data-target-id="${item.id}"
                             onclick="scrollToBlock('${item.id}')" 
                             style="background: rgba(245, 158, 11, 0.05); border-left: 3px solid #f59e0b;">
                            <div class="d-flex align-items-center">
                                <span class="outline-index fw-bold text-warning me-2" style="font-size: 0.8rem;">${romanNum}.</span>
                                <span class="outline-label fw-bold text-navy text-truncate" style="font-size: 0.85rem;">${virtualLabel}</span>
                            </div>
                        </div>
                    `;
                }
            }
            
            if (item.type === 'static-text' && item.content) {
                const hMatch = item.content.match(/<h([1-4])>(.*?)<\/h\1>/i);
                if (hMatch) {
                    const level = parseInt(hMatch[1]); // 1 to 4
                    const text = hMatch[2].replace(/<[^>]*>/g, '');
                    
                    // Update counters
                    counters[level - 1]++;
                    for (let i = level; i < 4; i++) counters[i] = 0;
                    
                    // Build numbering string (e.g., "1.", "1.1.", "1.1.1.")
                    let numbering = "";
                    for (let i = 0; i < level; i++) {
                        if (counters[i] > 0) numbering += counters[i] + ".";
                    }
                    
                    const marginLeft = (level - 1) * 15 + 10;
                    html += `
                        <div class="outline-item outline-h${level} px-2 py-1 mb-1 rounded cursor-pointer opacity-75" 
                             data-target-id="${item.id}"
                             onclick="scrollToBlock('${item.id}')" 
                             style="margin-left: ${marginLeft}px; font-size: 0.75rem; border-left: 1px solid #ddd;">
                            <div class="d-flex align-items-center">
                                <span class="outline-index small text-muted me-2" style="font-size: 0.7rem;">${numbering}</span>
                                <span class="text-muted text-truncate d-block" title="${text}">${text}</span>
                            </div>
                        </div>
                    `;
                }
            }
        });

        if (!html) {
            outlineContainer.innerHTML = '<div class="outline-empty small text-muted p-3 text-center" style="font-style: italic; background: #f8f9fa; border-radius: 8px; border: 1px dashed #ddd;">Chưa có phân đoạn nào. Hãy thêm "Phân đoạn" để tạo mục lục.</div>';
        } else {
            outlineContainer.innerHTML = html;
        }
    }

    function scrollToBlock(id) {
        // Find the element with data-id or the actual ID
        let el = document.querySelector(`[data-id="${id}"]`);
        if (!el) el = document.getElementById(id);
        
        if (el) {
            // Highlight the active item in TOC
            document.querySelectorAll('.outline-item').forEach(item => item.classList.remove('active-outline'));
            const tocItem = document.querySelector(`.outline-item[data-target-id="${id}"]`);
            if (tocItem) tocItem.classList.add('active-outline');

            // Scroll to the element with a slight offset for the fixed toolbar
            const offset = 120; // Height of the toolbar
            const elementPosition = el.getBoundingClientRect().top + window.pageYOffset;
            const offsetPosition = elementPosition - offset;

            window.scrollTo({
                top: offsetPosition,
                behavior: 'smooth'
            });

            // Highlight the target block in canvas
            document.querySelectorAll('.block-item').forEach(b => b.classList.remove('flash-highlight'));
            el.classList.add('flash-highlight');
            setTimeout(() => el.classList.remove('flash-highlight'), 2000);
        } else {
            console.warn('Block not found for scrolling:', id);
        }
    }


</script>

<style>
    .outline-item.active-outline {
        background: #e0f2fe !important;
        border-left: 4px solid #0284c7 !important;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }
    .outline-item.active-outline .outline-label {
        color: #0369a1 !important;
    }
    .outline-item:hover {
        background: rgba(14, 165, 233, 0.1) !important;
        transform: translateX(4px);
    }
    .flash-highlight {
        animation: flash-animation 2s ease-out;
    }
    @keyframes flash-animation {
        0% { background-color: rgba(14, 165, 233, 0.4); box-shadow: 0 0 25px rgba(14, 165, 233, 0.5); }
        100% { background-color: transparent; box-shadow: none; }
    }
</style>
