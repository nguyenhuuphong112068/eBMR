<script>
    document.addEventListener('DOMContentLoaded', function() {
        const gutter = document.getElementById('comment-gutter');
        const editorContent = document.getElementById('editor-content');
        const documentPage = document.getElementById('document-page');

        // Render existing comments
        window.renderComments = function() {
            gutter.innerHTML = '';
            if (!window.templateComments || window.templateComments.length === 0) {
                gutter.classList.add('d-none');
                return;
            }

            gutter.classList.remove('d-none');
            
            // We need to wait for DOM and Styles to be ready for offset calculations
            setTimeout(() => {
                // First pass: Calculate natural positions
                let commentData = window.templateComments.map(c => {
                    let naturalTop = 50;
                    let targetElement = null;
                    if (c.selection_id) {
                        targetElement = document.getElementById(c.selection_id);
                        if (targetElement) {
                            const pageRect = documentPage.getBoundingClientRect();
                            const targetRect = targetElement.getBoundingClientRect();
                            naturalTop = targetRect.top - pageRect.top;
                        }
                    }
                    return { ...c, naturalTop, targetElement };
                });

                // Sort by natural top
                commentData.sort((a, b) => a.naturalTop - b.naturalTop);

                // Second pass: Calculate adjusted positions to prevent overlap
                let lastBottom = -10; // start slightly above
                const minGap = 15;

                commentData.forEach((c, idx) => {
                    let adjustedTop = Math.max(c.naturalTop, lastBottom + minGap);
                    
                    const div = document.createElement('div');
                    div.className = 'comment-item';
                    div.id = 'box-' + c.selection_id;
                    div.style.top = adjustedTop + 'px';
                    
                    if (c.targetElement) {
                        const line = document.createElement('div');
                        line.className = 'comment-connector-line';
                        
                        const deltaY = adjustedTop - c.naturalTop + 15;
                        const width = 40; // Fixed bridge width in flex
                        const angle = Math.atan2(-deltaY, width) * (180 / Math.PI);
                        
                        line.style.width = width + 'px';
                        line.style.left = (-width) + 'px';
                        line.style.top = '25px';
                        line.style.transform = `rotate(${angle}deg)`;
                        line.style.transformOrigin = 'right center';
                        div.appendChild(line);
                    }

                    const initials = c.user_name ? c.user_name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2) : '??';

                    div.innerHTML += `
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="d-flex align-items-center">
                                <div class="comment-avatar me-2">${initials}</div>
                                <div>
                                    <div class="comment-user">${c.user_name}</div>
                                    <div class="comment-date font-italic small">${new Date(c.created_at).toLocaleString()}</div>
                                </div>
                            </div>
                            <button class="btn btn-sm btn-light text-danger border-0 rounded-circle" onclick="deleteComment(event, ${c.id}, '${c.selection_id}')" title="Xóa bình luận">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                        <div class="comment-content">${c.content}</div>
                    `;
                    
                    div.onclick = (e) => {
                        e.stopPropagation();
                        // Clear past actives
                        document.querySelectorAll('.comment-item, .ebmr-comment-highlight').forEach(el => el.classList.remove('active'));
                        
                        div.classList.add('active');
                        if (c.targetElement) {
                            c.targetElement.classList.add('active');
                            c.targetElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }
                    };
                    gutter.appendChild(div);
                    lastBottom = adjustedTop + 80;
                });

                // Add listener to highlights themselves to activate the box
                document.querySelectorAll('.ebmr-comment-highlight').forEach(highlight => {
                    highlight.onclick = (e) => {
                        e.stopPropagation();
                        const box = document.getElementById('box-' + highlight.id);
                        if (box) box.click();
                    };
                });

                // Refine: Wait for rendering then correct heights
                setTimeout(() => {
                    let currentBottom = -10;
                    document.querySelectorAll('.comment-item').forEach((box, i) => {
                        let top = parseInt(box.style.top);
                        if (top < currentBottom + minGap) {
                            top = currentBottom + minGap;
                            box.style.top = top + 'px';
                        }
                        currentBottom = top + box.offsetHeight;
                    });
                }, 50);

            }, 100);
        };

        // Initial render
        renderComments();



        window.addComment = function() {
            const sel = window.getSelection();
            if (sel.rangeCount > 0 && !sel.isCollapsed) {
                const range = sel.getRangeAt(0);
                
                // Ensure selection is inside editor content
                let node = sel.anchorNode;
                if (node.nodeType === 3) node = node.parentNode;
                if (!editorContent.contains(node)) {
                    toastr.warning('Vui lòng chọn văn bản trong vùng soạn thảo');
                    return;
                }

                Swal.fire({
                    title: 'Thêm bình luận',
                    input: 'textarea',
                    inputPlaceholder: 'Nhập nội dung ghi chú...',
                    showCancelButton: true,
                    confirmButtonText: 'Lưu',
                    cancelButtonText: 'Hủy'
                }).then((result) => {
                    if (result.isConfirmed && result.value) {
                        const commentId = 'comment_mark_' + Date.now();
                        
                        // Wrap selection in a highlight span
                        const span = document.createElement('span');
                        span.id = commentId;
                        span.className = 'ebmr-comment-highlight';
                        try {
                            range.surroundContents(span);
                        } catch (e) {
                            // If selection spans across multiple blocks, surroundContents might fail
                            // We can use a simpler approach or notify user
                            toastr.error('Không thể bình luận trên vùng chọn phức tạp. Vui lòng chọn trong cùng một đoạn.');
                            return;
                        }

                        // Save to DB
                        saveComment(commentId, result.value);
                    }
                });
            } else {
                toastr.warning('Vui lòng quét chọn đoạn văn bản để bình luận');
            }
        };

        // floatingBtn.onclick = window.addComment; // Removed

        function saveComment(selectionId, content) {
            $.post('{{ route('pages.ebmr.storeComment') }}', {
                _token: '{{ csrf_token() }}',
                template_id: '{{ $template->id ?? 0 }}',
                content: content,
                selection_id: selectionId
            }, function(res) {
                if (res.success) {
                    window.templateComments.push(res.comment);
                    renderComments();
                }
            });
        }

        window.deleteComment = function(e, id, selectionId) {
            e.stopPropagation();
            Swal.fire({
                title: 'Xóa bình luận?',
                text: "Bạn không thể hoàn tác hành động này!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Xóa',
                cancelButtonText: 'Hủy'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('{{ route('pages.ebmr.deleteComment') }}', {
                        _token: '{{ csrf_token() }}',
                        id: id
                    }, function(res) {
                        if (res.success) {
                            // Find highlight span and unwrap it
                            const span = document.getElementById(selectionId);
                            if (span) {
                                span.replaceWith(...span.childNodes);
                            }
                            
                            // Remove from local list and re-render
                            window.templateComments = window.templateComments.filter(c => c.id !== id);
                            renderComments();
                        }
                    });
                }
            });
        };
    });
</script>
