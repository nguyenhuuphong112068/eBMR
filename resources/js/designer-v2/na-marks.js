/**
 * Gạch chéo "KHÔNG SỬ DỤNG" (N/A) — Chạy thử / Thực thi lô V2.
 * =============================================================
 * Port từ V1 (markSelectedZoneAsNA / _na_state trong ui_handlers.blade.php)
 * sang V2, thiết kế lại cho MÁY TÍNH BẢNG (không chuột, không phím tắt):
 *
 *  - Nút "Gạch chéo N/A" trên toolbar bật CHẾ ĐỘ GẠCH CHÉO: mọi cú chạm
 *    trong tài liệu chỉ dùng để CHỌN (không mở nhập liệu), thanh hành động
 *    lớn hiện ở đáy màn hình với các nút cỡ ngón tay (>=46px).
 *  - Chạm 1 ô bảng      -> chọn/bỏ chọn ô đó (chạm nhiều ô = chọn nhiều).
 *  - Chạm 1 khối văn bản -> chọn/bỏ chọn cả khối.
 *  - Nút [Hàng] [Cột] [Bảng] áp theo Ô CHẠM GẦN NHẤT; nút [Vùng] chọn
 *    hình chữ nhật giữa 2 ô chạm gần nhất (cùng bảng).
 *  - [Gạch chéo…] hỏi LÝ DO KHÔNG SỬ DỤNG (bắt buộc) rồi vẽ 2 đường chéo
 *    đỏ (SVG — in ấn được) + chip lý do đè lên mục; nội dung bên dưới mờ đi
 *    và bị chặn nhập liệu. [Hủy gạch…] cũng bắt buộc lý do (chuẩn GMP).
 *  - Ngoài chế độ gạch chéo: chạm vào vùng đã gạch -> popup xem lý do /
 *    người / thời gian, kèm nút hủy gạch (nếu hồ sơ chưa khóa).
 *
 * LƯU TRỮ: đi chung đường với giá trị nhập liệu, KHÔNG cần sửa backend —
 *   BOOT.executionValues.__na__[targetKey] = { reason, by, at } | '' (đã hủy)
 *   targetKey = "<blockId>"            -> gạch cả khối / cả bảng
 *             = "<blockId>:<r>_<c>"    -> gạch 1 ô bảng
 * updateRecordData lưu mỗi targetKey thành 1 dòng ebmr_run_data
 * (block_uuid='__na__', cell_id=targetKey, raw_value=JSON) nên hủy gạch cũng
 * có lịch sử trong ebmr_run_data_history. Khi HỦY (ghi đè JSON cũ bằng '')
 * server đòi lý do thay đổi -> getPendingReasons() trả map lý do để
 * saveRecordDataV2 gửi kèm dưới reasons['__na__'].
 */

export function createNaMarksV2(ctx) {
    const { BOOT } = ctx;

    let active = false;          // đang bật chế độ gạch chéo
    const picked = new Set();    // targetKey đang chọn
    const tapTrail = [];         // tối đa 2 lần chạm Ô gần nhất: {blockId, r, c}
    const pendingReasons = {};   // targetKey -> lý do gần nhất (gửi kèm khi lưu)

    /* ── State helpers ──────────────────────────────────────── */
    function naState() {
        if (typeof BOOT.executionValues !== 'object' || BOOT.executionValues === null) BOOT.executionValues = {};
        if (typeof BOOT.executionValues.__na__ !== 'object' || BOOT.executionValues.__na__ === null) BOOT.executionValues.__na__ = {};
        return BOOT.executionValues.__na__;
    }
    function markInfo(key) {
        const v = naState()[key];
        return (v && typeof v === 'object') ? v : null;
    }

    const CELL_SUFFIX_RE = /^(\d+)_(\d+)$/;
    /** "blockId:r_c" -> {blockId, r, c}; key khối -> {blockId} */
    function parseKey(key) {
        const i = key.lastIndexOf(':');
        if (i > 0) {
            const m = CELL_SUFFIX_RE.exec(key.slice(i + 1));
            if (m) return { blockId: key.slice(0, i), r: parseInt(m[1], 10), c: parseInt(m[2], 10) };
        }
        return { blockId: key };
    }
    const cellKey = (blockId, r, c) => `${blockId}:${r}_${c}`;
    const cssEsc = (s) => (window.CSS && window.CSS.escape ? window.CSS.escape(s) : s);

    /** MỌI phần tử khớp key — block trong "Lặp nhóm" render lặp lại cùng data-id
     *  cho từng tab "Lần i", nên gạch chéo áp dụng chung cho tất cả các lần lặp. */
    function findTargetEls(key) {
        const p = parseKey(key);
        const sel = p.r === undefined
            ? `#v2-pages .v2-block[data-id="${cssEsc(p.blockId)}"]`
            : `#v2-pages .v2-table-wrap[data-id="${cssEsc(p.blockId)}"] td[data-row="${p.r}"][data-col="${p.c}"]`;
        return Array.from(document.querySelectorAll(sel));
    }

    function nowVi() {
        const d = new Date();
        return d.toLocaleDateString('vi-VN') + ' ' + d.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
    }
    function escHtml(s) {
        return String(s).replace(/[&<>"']/g, (c) => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
        }[c]));
    }
    function toast(title, icon = 'success') {
        window.Swal?.fire({ toast: true, position: 'top-end', icon, title, showConfirmButton: false, timer: 2200 });
    }

    /* ── Vẽ gạch chéo lên DOM (gọi sau MỖI renderDocument) ────
     * opts:
     *   isBlock     - mục cấp khối (bảng/khối văn bản)
     *   isGroup     - lớp phủ cấp VÙNG (nhiều ô cùng 1 lần gạch) đặt trên .v2-table-wrap
     *   showLine    - có vẽ đường chéo không (ô con của vùng thì KHÔNG, để chỉ 1 đường)
     *   showReason  - có hiện chip lý do không
     *   interactive - true: cả lớp phủ chặn nhập + chạm xem lý do (khóa ô đã gạch);
     *                 false: lớp phủ để CHO XUYÊN QUA (không chặn ô kế bên), chỉ chip bắt chạm
     */
    function buildOverlay(keys, info, opts) {
        opts = opts || {};
        const { isBlock = false, isGroup = false, showLine = true, showReason = true, interactive = true } = opts;
        const keyList = Array.isArray(keys) ? keys : [keys];
        const ov = document.createElement('div');
        ov.className = 'v2-na-x'
            + (isBlock ? ' v2-na-x-block' : '')
            + (isGroup ? ' v2-na-x-group' : '')
            + (interactive ? '' : ' v2-na-x-passive');
        ov.setAttribute('data-na-key', keyList[0] || '');
        if (keyList.length > 1) ov.setAttribute('data-na-keys', keyList.join(','));
        const reason = info.reason || 'Không sử dụng';
        const meta = [info.by, info.at].filter(Boolean).join(' — ');
        ov.title = `Không sử dụng: ${reason}${meta ? `\n${meta}` : ''}`;
        // SVG (không phải background CSS) để đường chéo LUÔN in ra giấy — 1 đường mảnh
        // màu xanh dương từ góc TRÊN-PHẢI xuống góc DƯỚI-TRÁI.
        let html = '';
        if (showLine) {
            html += `<svg viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true">
                <line x1="100" y1="0" x2="0" y2="100" vector-effect="non-scaling-stroke"></line>
            </svg>`;
        }
        // Chữ chú thích viết SONG SONG với đường gạch (như hồ sơ giấy): LÝ DO nằm 1 bên
        // đường gạch, NGƯỜI + THỜI GIAN nằm bên kia. Góc xoay khớp đúng đường chéo được
        // tính sau khi chèn (setLabelAngles) theo tỉ lệ thật của khung.
        if (showReason) {
            html += `<span class="v2-na-reason v2-na-reason--main">${escHtml(reason)}</span>`;
            if (meta) html += `<span class="v2-na-reason v2-na-reason--meta">${escHtml(meta)}</span>`;
        }
        ov.innerHTML = html;
        // Ngoài chế độ gạch chéo: chạm để xem lý do / hủy gạch. Trong chế độ
        // gạch chéo, onClickCapture xử lý trước (capture) nên listener này không chạy.
        const openInfo = (e) => { if (active) return; e.stopPropagation(); showInfo(keyList); };
        if (interactive) {
            ov.addEventListener('click', openInfo);
        } else {
            // Lớp đường-chéo cấp vùng KHÔNG chặn ô bên dưới; chỉ chữ chú thích bắt chạm
            ov.querySelectorAll('.v2-na-reason').forEach((chip) => chip.addEventListener('click', openInfo));
        }
        return ov;
    }

    /** Vẽ 1 đường chéo DUY NHẤT phủ khung bao của nhiều ô cùng 1 group (đặt trên .v2-table-wrap) */
    function drawGroupOverlay(cluster) {
        const wrap = cluster.wrap;
        if (!wrap) return;
        if (getComputedStyle(wrap).position === 'static') wrap.style.position = 'relative';
        const wr = wrap.getBoundingClientRect();
        let minL = Infinity, minT = Infinity, maxR = -Infinity, maxB = -Infinity;
        cluster.pairs.forEach(({ el }) => {
            const r = el.getBoundingClientRect();
            if (r.width === 0 && r.height === 0) return;
            minL = Math.min(minL, r.left); minT = Math.min(minT, r.top);
            maxR = Math.max(maxR, r.right); maxB = Math.max(maxB, r.bottom);
        });
        if (!isFinite(minL)) return;
        const keys = cluster.pairs.map((p) => p.key);
        const ov = buildOverlay(keys, cluster.info, { isGroup: true, showLine: true, showReason: true, interactive: false });
        ov.style.left = (minL - wr.left + wrap.scrollLeft) + 'px';
        ov.style.top = (minT - wr.top + wrap.scrollTop) + 'px';
        ov.style.width = (maxR - minL) + 'px';
        ov.style.height = (maxB - minT) + 'px';
        wrap.appendChild(ov);
    }

    function onAfterRender() {
        if (!BOOT.isExecutionMode) return; // Thiết kế: không vẽ gạch chéo (dữ liệu vẫn giữ trong bộ nhớ)
        // Dọn lớp phủ vùng còn sót (renderDocument dựng lại DOM ô, nhưng lớp vùng bám .v2-table-wrap)
        document.querySelectorAll('#v2-pages .v2-na-x-group').forEach((el) => el.remove());
        const na = BOOT.executionValues && BOOT.executionValues.__na__;
        if (na) {
            // Gom các ô cùng group (và cùng 1 bảng) thành cụm để chỉ vẽ 1 đường chéo
            const clusters = new Map(); // groupId::tableId -> { info, keys:Set, els:[], wrap }
            Object.keys(na).forEach((key) => {
                if (key.startsWith('_')) return; // _meta do server/luồng lưu chèn vào
                const info = markInfo(key);
                if (!info) return;
                const isBlock = parseKey(key).r === undefined;
                const els = findTargetEls(key);
                if (!els.length) return; // khác section đang xem -> bỏ qua
                els.forEach((el) => el.classList.add(isBlock ? 'v2-na-block' : 'v2-na-cell'));

                if (!isBlock && info.group) {
                    // Ô bảng có group: gom theo (group, bảng) để quyết định vẽ 1 hay nhiều đường.
                    els.forEach((el) => {
                        const wrap = el.closest('.v2-table-wrap[data-id]');
                        if (!wrap) { el.appendChild(buildOverlay(key, info, {})); return; }
                        const ck = info.group + '::' + wrap.getAttribute('data-id');
                        if (!clusters.has(ck)) clusters.set(ck, { info, pairs: [], wrap });
                        clusters.get(ck).pairs.push({ el, key });
                    });
                } else {
                    // Khối / ô lẻ: lớp phủ trọn gói (đường + chip) ngay trên phần tử
                    els.forEach((el) => el.appendChild(buildOverlay(key, info, { isBlock })));
                }
            });
            // Với mỗi cụm: 1 ô -> lớp trọn gói; nhiều ô -> mỗi ô 1 lớp KHÓA trong suốt
            // (chặn nhập + chạm chọn/xem đúng ô đó) + CHỈ 1 đường chéo phủ khung bao cả vùng.
            clusters.forEach((c) => {
                if (c.pairs.length === 1) {
                    c.pairs[0].el.appendChild(buildOverlay(c.pairs[0].key, c.info, {}));
                } else {
                    c.pairs.forEach((p) => p.el.appendChild(buildOverlay(p.key, c.info, { showLine: false, showReason: false })));
                    drawGroupOverlay(c);
                }
            });
        }
        setLabelAngles();
        if (active) paintPicked();
    }

    /** Xoay chữ chú thích cho SONG SONG đúng với đường gạch (top-phải -> dưới-trái)
     *  theo tỉ lệ THẬT của từng khung — khung rộng/thấp thì đường gạch thoải, chữ theo đó. */
    function setLabelAngles() {
        document.querySelectorAll('#v2-pages .v2-na-x').forEach((ov) => {
            if (!ov.querySelector('.v2-na-reason')) return;
            const w = ov.offsetWidth, h = ov.offsetHeight;
            if (!w || !h) return;
            // Đường từ (W,0) -> (0,H): vector hướng (W,-H) => góc âm (đi lên bên phải)
            const deg = Math.atan2(-h, w) * 180 / Math.PI;
            ov.style.setProperty('--na-angle', deg.toFixed(2) + 'deg');
        });
    }

    /* ── Chọn mục (chế độ gạch chéo) ────────────────────────── */
    function paintPicked() {
        document.querySelectorAll('#v2-pages .v2-na-picked')
            .forEach((el) => el.classList.remove('v2-na-picked'));
        picked.forEach((key) => findTargetEls(key).forEach((el) => el.classList.add('v2-na-picked')));
        updateBar();
    }

    function togglePick(key) {
        if (!key) return;
        if (picked.has(key)) picked.delete(key);
        else picked.add(key);
        paintPicked();
    }

    function pushTap(tap) {
        tapTrail.push(tap);
        if (tapTrail.length > 2) tapTrail.shift();
    }

    function lastTap() { return tapTrail[tapTrail.length - 1] || null; }

    /** Các td đang hiển thị của 1 bảng (theo blockId) */
    function tableTds(blockId) {
        return Array.from(document.querySelectorAll(
            `#v2-pages .v2-table-wrap[data-id="${cssEsc(blockId)}"] td[data-row]`));
    }

    function pickRow() {
        const t = lastTap();
        if (!t) { toast('Hãy chạm 1 ô trong bảng trước', 'info'); return; }
        tableTds(t.blockId).forEach((td) => {
            if (parseInt(td.dataset.row, 10) === t.r) picked.add(cellKey(t.blockId, t.r, parseInt(td.dataset.col, 10)));
        });
        paintPicked();
    }

    function pickCol() {
        const t = lastTap();
        if (!t) { toast('Hãy chạm 1 ô trong bảng trước', 'info'); return; }
        tableTds(t.blockId).forEach((td) => {
            if (parseInt(td.dataset.col, 10) === t.c) picked.add(cellKey(t.blockId, parseInt(td.dataset.row, 10), t.c));
        });
        paintPicked();
    }

    function pickTable() {
        const t = lastTap();
        if (!t) { toast('Hãy chạm 1 ô trong bảng trước', 'info'); return; }
        // Cả bảng = 1 mục cấp KHỐI (1 lớp gạch chéo phủ nguyên bảng)
        tableTds(t.blockId).forEach((td) => picked.delete(cellKey(t.blockId, parseInt(td.dataset.row, 10), parseInt(td.dataset.col, 10))));
        picked.add(t.blockId);
        paintPicked();
    }

    function pickRange() {
        if (tapTrail.length < 2 || tapTrail[0].blockId !== tapTrail[1].blockId) {
            toast('Chạm 2 ô góc (cùng 1 bảng) rồi bấm "Vùng"', 'info');
            return;
        }
        const [a, b] = tapTrail;
        const minR = Math.min(a.r, b.r), maxR = Math.max(a.r, b.r);
        const minC = Math.min(a.c, b.c), maxC = Math.max(a.c, b.c);
        tableTds(a.blockId).forEach((td) => {
            const r = parseInt(td.dataset.row, 10), c = parseInt(td.dataset.col, 10);
            if (r >= minR && r <= maxR && c >= minC && c <= maxC) picked.add(cellKey(a.blockId, r, c));
        });
        paintPicked();
    }

    /* ── Đánh dấu / Hủy đánh dấu ────────────────────────────── */
    function applyMark(keys, reason) {
        const by = BOOT.currentUserName || 'Người dùng';
        const at = nowVi();
        // Cùng 1 lần gạch nhiều ô -> chung 1 "group" để vẽ CHỈ 1 đường chéo phủ cả vùng
        // (thay vì mỗi ô 1 đường). group đi kèm dữ liệu nên tải lại vẫn gộp đúng.
        const group = 'g' + Date.now().toString(36) + Math.random().toString(36).slice(2, 6);
        keys.forEach((key) => {
            naState()[key] = { reason, by, at, group };
            pendingReasons[key] = reason;
        });
    }

    function promptMark() {
        const keys = Array.from(picked).filter((k) => !markInfo(k));
        if (!keys.length) { toast('Chưa chọn mục nào cần gạch chéo', 'info'); return; }
        window.Swal.fire({
            title: 'Gạch chéo — Không sử dụng',
            html: `<div class="text-start mb-2">Gạch chéo <b>${keys.length}</b> mục đã chọn. Nội dung không bị xóa, chỉ đánh dấu <b>KHÔNG SỬ DỤNG</b> và khóa nhập liệu.</div>`,
            input: 'textarea',
            inputPlaceholder: 'Lý do không sử dụng (bắt buộc)...',
            inputAttributes: { rows: 3 },
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-ban me-1"></i> Gạch chéo',
            confirmButtonColor: '#dc3545',
            cancelButtonText: 'Hủy',
            inputValidator: (v) => (!v || !v.trim()) ? 'Vui lòng nhập lý do không sử dụng!' : undefined,
        }).then((res) => {
            if (!res.isConfirmed) return;
            applyMark(keys, res.value.trim());
            picked.clear();
            tapTrail.length = 0;
            ctx.renderDocument();
            // Lưu ngay sau khi gạch chéo (không còn nút Lưu bản nháp)
            window.__V2__?.autoSaveRecordData?.();
            toast(`Đã gạch chéo & lưu ${keys.length} mục`);
        });
    }

    function promptUnmark(keys) {
        keys = keys.filter((k) => markInfo(k));
        if (!keys.length) { toast('Chưa chọn mục nào đang bị gạch chéo', 'info'); return; }
        window.Swal.fire({
            title: 'Hủy gạch chéo',
            html: `<div class="text-start mb-2">Khôi phục <b>${keys.length}</b> mục về trạng thái sử dụng bình thường.</div>`,
            input: 'textarea',
            inputPlaceholder: 'Lý do hủy gạch chéo (bắt buộc)...',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-undo me-1"></i> Hủy gạch chéo',
            cancelButtonText: 'Đóng',
            inputValidator: (v) => (!v || !v.trim()) ? 'Vui lòng nhập lý do hủy gạch chéo!' : undefined,
        }).then((res) => {
            if (!res.isConfirmed) return;
            const reason = res.value.trim();
            keys.forEach((key) => {
                naState()[key] = ''; // rỗng = đã hủy (server ghi history JSON cũ -> '')
                pendingReasons[key] = reason;
            });
            keys.forEach((k) => picked.delete(k));
            ctx.renderDocument();
            // Lưu ngay sau khi hủy gạch chéo (không còn nút Lưu bản nháp)
            window.__V2__?.autoSaveRecordData?.();
            toast('Đã hủy gạch chéo & lưu', 'info');
        });
    }

    function showInfo(keyOrKeys) {
        const keys = Array.isArray(keyOrKeys) ? keyOrKeys : [keyOrKeys];
        const info = markInfo(keys[0]);
        if (!info) return;
        const canEdit = !BOOT.isReadOnly;
        const multiNote = keys.length > 1 ? `<div class="small text-muted mt-1"><i class="fas fa-object-group me-1"></i>${keys.length} ô cùng 1 lần gạch</div>` : '';
        window.Swal.fire({
            title: '<i class="fas fa-ban me-2 text-primary"></i>Không sử dụng',
            html: `
                <div class="text-start">
                    <div class="mb-1"><b>Lý do:</b> ${escHtml(info.reason || '')}</div>
                    <div class="small text-muted">${escHtml(info.by || '')}${info.at ? ' — ' + escHtml(info.at) : ''}</div>
                    ${multiNote}
                </div>`,
            showDenyButton: canEdit,
            denyButtonText: '<i class="fas fa-undo me-1"></i> Hủy gạch chéo…',
            confirmButtonText: 'Đóng',
        }).then((res) => {
            if (res.isDenied) promptUnmark(keys);
        });
    }

    /* ── Thanh hành động đáy màn hình (cỡ ngón tay) ─────────── */
    let barEl = null;
    function buildBar() {
        if (barEl) return;
        barEl = document.createElement('div');
        barEl.id = 'v2-na-bar';
        barEl.innerHTML = `
            <span class="v2-na-bar-info"><i class="fas fa-ban me-1"></i><span data-na-count>0</span> mục</span>
            <button type="button" data-na-act="row" title="Chọn cả hàng chứa ô vừa chạm"><i class="fas fa-grip-lines"></i> Hàng</button>
            <button type="button" data-na-act="col" title="Chọn cả cột chứa ô vừa chạm"><i class="fas fa-grip-lines-vertical"></i> Cột</button>
            <button type="button" data-na-act="table" title="Chọn cả bảng chứa ô vừa chạm"><i class="fas fa-table"></i> Bảng</button>
            <button type="button" data-na-act="range" title="Chọn vùng chữ nhật giữa 2 ô chạm gần nhất"><i class="fas fa-vector-square"></i> Vùng</button>
            <button type="button" data-na-act="clear" title="Bỏ chọn tất cả"><i class="fas fa-eraser"></i></button>
            <button type="button" data-na-act="mark" class="v2-na-btn-danger" title="Gạch chéo các mục đã chọn (ghi lý do)"><i class="fas fa-ban"></i> Gạch chéo</button>
            <button type="button" data-na-act="unmark" title="Hủy gạch chéo các mục đã chọn"><i class="fas fa-undo"></i> Hủy gạch</button>
            <button type="button" data-na-act="exit" class="v2-na-btn-done" title="Thoát chế độ gạch chéo"><i class="fas fa-check"></i> Xong</button>`;
        barEl.addEventListener('click', (e) => {
            const btn = e.target.closest('button[data-na-act]');
            if (!btn) return;
            e.stopPropagation();
            const act = btn.getAttribute('data-na-act');
            if (act === 'row') pickRow();
            else if (act === 'col') pickCol();
            else if (act === 'table') pickTable();
            else if (act === 'range') pickRange();
            else if (act === 'clear') { picked.clear(); tapTrail.length = 0; paintPicked(); }
            else if (act === 'mark') promptMark();
            else if (act === 'unmark') promptUnmark(Array.from(picked));
            else if (act === 'exit') exit();
        });
        document.body.appendChild(barEl);
    }

    function updateBar() {
        if (!barEl) return;
        const countEl = barEl.querySelector('[data-na-count]');
        if (countEl) countEl.textContent = String(picked.size);
        const hasTap = !!lastTap();
        const markable = Array.from(picked).some((k) => !markInfo(k));
        const unmarkable = Array.from(picked).some((k) => !!markInfo(k));
        const set = (act, en) => { const b = barEl.querySelector(`[data-na-act="${act}"]`); if (b) b.disabled = !en; };
        set('row', hasTap); set('col', hasTap); set('table', hasTap);
        set('range', tapTrail.length >= 2 && tapTrail[0].blockId === tapTrail[1].blockId);
        set('clear', picked.size > 0);
        set('mark', markable);
        set('unmark', unmarkable);
    }

    /* ── Bắt sự kiện chạm khi đang ở chế độ gạch chéo ───────── */
    function inPages(target) {
        const pages = document.getElementById('v2-pages');
        return pages && pages.contains(target);
    }

    function onClickCapture(e) {
        if (!active) return;
        if (e.target.closest('#v2-na-bar') || e.target.closest('.swal2-container')
            || e.target.closest('#v2-toolbar') || e.target.closest('.modal')
            || e.target.closest('.v2-loop-tabs-header')) return; // vẫn cho chuyển tab "Lần i"
        if (!inPages(e.target)) return;
        // Nuốt cú chạm: không mở editor/nhập liệu, chỉ dùng để CHỌN
        e.preventDefault();
        e.stopPropagation();

        const ov = e.target.closest('.v2-na-x');
        if (ov) {
            const multi = ov.getAttribute('data-na-keys');
            if (multi) multi.split(',').forEach((k) => togglePick(k));
            else togglePick(ov.getAttribute('data-na-key'));
            return;
        }

        const td = e.target.closest('td[data-row]');
        const wrap = td && td.closest('.v2-table-wrap[data-id]');
        if (td && wrap) {
            const blockId = wrap.getAttribute('data-id');
            const r = parseInt(td.dataset.row, 10);
            const c = parseInt(td.dataset.col, 10);
            pushTap({ blockId, r, c });
            togglePick(cellKey(blockId, r, c));
            return;
        }

        const blockEl = e.target.closest('.v2-block[data-id]');
        if (blockEl) togglePick(blockEl.getAttribute('data-id'));
    }

    // Chặn từ mousedown để không focus/mở bàn phím ảo dưới lớp chọn
    function onMouseDownCapture(e) {
        if (!active) return;
        if (e.target.closest('#v2-na-bar') || e.target.closest('.swal2-container')
            || e.target.closest('#v2-toolbar') || e.target.closest('.modal')
            || e.target.closest('.v2-loop-tabs-header')) return;
        if (!inPages(e.target)) return;
        e.preventDefault();
        e.stopPropagation();
    }

    /* ── Bật / tắt chế độ ───────────────────────────────────── */
    function refreshButton() {
        const btn = document.getElementById('v2-btn-na-mode');
        if (!btn) return;
        const usable = !!BOOT.isExecutionMode && !BOOT.isReadOnly;
        btn.classList.toggle('d-none', !usable);
        btn.classList.toggle('active', active);
        if (!usable && active) exit();
    }

    function enter() {
        if (active) return;
        active = true;
        ctx.unmountEditor();
        document.body.classList.add('v2-na-mode');
        buildBar();
        barEl.classList.add('show');
        paintPicked();
        refreshButton();
        toast('Chế độ gạch chéo: chạm để chọn ô / khối, rồi bấm "Gạch chéo"', 'info');
    }

    function exit() {
        if (!active) return;
        active = false;
        picked.clear();
        tapTrail.length = 0;
        document.body.classList.remove('v2-na-mode');
        if (barEl) barEl.classList.remove('show');
        document.querySelectorAll('#v2-pages .v2-na-picked')
            .forEach((el) => el.classList.remove('v2-na-picked'));
        refreshButton();
    }

    function toggle() { if (active) exit(); else enter(); }

    function init() {
        document.addEventListener('click', onClickCapture, true);
        document.addEventListener('mousedown', onMouseDownCapture, true);
        document.getElementById('v2-btn-na-mode')?.addEventListener('click', (e) => {
            e.preventDefault();
            toggle();
        });
        refreshButton();
    }

    /** Map lý do gạch/hủy gạch trong phiên — saveRecordDataV2 gửi kèm dưới reasons['__na__'] */
    const getPendingReasons = () => pendingReasons;

    return { init, onAfterRender, toggle, exit, refreshButton, getPendingReasons, isActive: () => active };
}
