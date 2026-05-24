<script>
    document.addEventListener('DOMContentLoaded', function() {
        const gutter = document.getElementById('comment-gutter');
        const editorContent = document.getElementById('editor-content');
        const documentPage = document.getElementById('document-page');

        // Default comments visibility to hidden on page load
        window.commentsHidden = true;

        // Load persisted comment card offsets from localStorage
        const storageKey = 'ebmr_comment_offsets_' + ('{{ $template->id ?? 0 }}');
        let savedOffsets = {};
        try {
            savedOffsets = JSON.parse(localStorage.getItem(storageKey)) || {};
        } catch(e) {}
        window.manualCommentOffsets = savedOffsets;

        function updateConnectorLine(div, targetElement) {
            if (!div || !targetElement) return;
            
            // Remove existing connector lines if any
            div.querySelectorAll('.comment-connector-line').forEach(el => el.remove());

            const line = document.createElement('div');
            line.className = 'comment-connector-line';
            line.style.transition = 'none'; // No transition lag during drag
            
            const targetRect = targetElement.getBoundingClientRect();
            const cardRect = div.getBoundingClientRect();
            const workspace = document.getElementById('designer-workspace');
            if (!workspace) return;
            const workspaceRect = workspace.getBoundingClientRect();

            // Target point (right side of highlight span, middle vertically)
            const targetX = targetRect.right - workspaceRect.left;
            const targetY = targetRect.top + (targetRect.height / 2) - workspaceRect.top;

            // Card point (left side of card, 25px down from card's top)
            const cardX = cardRect.left - workspaceRect.left;
            const cardY = cardRect.top + 25 - workspaceRect.top;

            const dx = targetX - cardX;
            const dy = targetY - cardY;

            const length = Math.sqrt(dx * dx + dy * dy);
            const angle = Math.atan2(dy, dx) * (180 / Math.PI);

            line.style.width = length + 'px';
            line.style.left = '0px';
            line.style.top = '25px';
            line.style.transform = `rotate(${angle}deg)`;
            line.style.transformOrigin = 'left center';
            
            div.appendChild(line);
        }

        // Render existing comments
        window.renderComments = function() {
            gutter.innerHTML = '';
            if (!window.templateComments || window.templateComments.length === 0 || window.commentsHidden) {
                gutter.classList.add('d-none');
                return;
            }

            gutter.classList.remove('d-none');
            
            // We need to wait for DOM to be ready
            setTimeout(() => {
                // First pass: Calculate natural positions and prepare list
                let commentData = [];
                const workspace = document.getElementById('designer-workspace');
                if (!workspace) return;
                const workspaceRect = workspace.getBoundingClientRect();

                window.templateComments.forEach(c => {
                    if (c.selection_id) {
                        const targetElement = document.getElementById(c.selection_id);
                        if (targetElement) {
                            const targetRect = targetElement.getBoundingClientRect();
                            const naturalTop = targetRect.top - workspaceRect.top;
                            commentData.push({ ...c, naturalTop, targetElement });
                        }
                    }
                });

                // Sort by natural top
                commentData.sort((a, b) => a.naturalTop - b.naturalTop);

                // Create cards in DOM to get their actual heights
                commentData.forEach(c => {
                    const div = document.createElement('div');
                    div.className = 'comment-item';
                    div.id = 'box-' + c.selection_id;
                    
                    // Parse content to check for replies
                    let commentText = c.content;
                    let replies = [];
                    try {
                        const parsed = JSON.parse(c.content);
                        if (parsed && typeof parsed === 'object' && parsed.hasOwnProperty('text')) {
                            commentText = parsed.text;
                            replies = parsed.replies || [];
                        }
                    } catch(e) {}

                    const initials = c.user_name ? c.user_name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2) : '??';

                    // Build replies HTML
                    let repliesHtml = '';
                    if (replies.length > 0) {
                        repliesHtml = '<div class="comment-replies-list mt-2 pt-2 border-top" style="font-size: 0.82rem;">';
                        replies.forEach(r => {
                            const rInitials = r.user_name ? r.user_name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2) : '??';
                            repliesHtml += `
                                <div class="reply-item d-flex align-items-start mb-2 ps-2" style="border-left: 2px solid #cbd5e1; margin-left: 10px;">
                                    <div class="comment-avatar me-2" style="width: 24px; height: 24px; font-size: 0.65rem; background: #64748b;">${rInitials}</div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="reply-user fw-bold text-dark" style="font-size: 0.8rem;">${r.user_name}</span>
                                            <span class="reply-date text-muted font-italic" style="font-size: 0.68rem;">${new Date(r.created_at).toLocaleString()}</span>
                                        </div>
                                        <div class="reply-content text-muted mt-1" style="font-size: 0.78rem;">${r.content}</div>
                                    </div>
                                </div>
                            `;
                        });
                        repliesHtml += '</div>';
                    }

                    // Build Reply Form
                    const replyFormHtml = `
                        <div class="comment-reply-form mt-2 pt-2 border-top d-none" id="reply-form-${c.id}">
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control form-control-sm" placeholder="Nhập phản hồi..." id="reply-input-${c.id}" onkeydown="if(event.key === 'Enter') { event.stopPropagation(); submitCommentReply(${c.id}); }">
                                <button class="btn btn-primary btn-sm" type="button" onclick="event.stopPropagation(); submitCommentReply(${c.id})">Gửi</button>
                            </div>
                        </div>
                    `;

                    div.innerHTML = `
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="d-flex align-items-center">
                                <div class="comment-avatar me-2">${initials}</div>
                                <div>
                                    <div class="comment-user">${c.user_name}</div>
                                    <div class="comment-date font-italic small">${new Date(c.created_at).toLocaleString()}</div>
                                </div>
                            </div>
                            <div class="d-flex gap-1">
                                <button class="btn btn-sm btn-light text-primary border-0 rounded-circle" onclick="event.stopPropagation(); toggleReplyForm(event, ${c.id})" title="Trả lời">
                                    <i class="fas fa-reply"></i>
                                </button>
                                <button class="btn btn-sm btn-light text-danger border-0 rounded-circle" onclick="deleteComment(event, ${c.id}, '${c.selection_id}')" title="Xóa bình luận">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </div>
                        <div class="comment-content">${commentText}</div>
                        ${repliesHtml}
                        ${replyFormHtml}
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
                });

                // Add listener to highlights themselves to activate the box
                document.querySelectorAll('.ebmr-comment-highlight').forEach(highlight => {
                    highlight.onclick = (e) => {
                        e.stopPropagation();
                        const box = document.getElementById('box-' + highlight.id);
                        if (box) box.click();
                    };
                });

                // Wait for the browser to draw the items to get accurate offsetHeights
                setTimeout(() => {
                    let lastBottom = -10; // start slightly above
                    const minGap = 15;

                    commentData.forEach(c => {
                        const div = document.getElementById('box-' + c.selection_id);
                        if (!div) return;

                        window.manualCommentOffsets = window.manualCommentOffsets || {};
                        let adjustedTop = c.naturalTop;
                        let adjustedLeft = 0;
                        
                        const savedVal = window.manualCommentOffsets[c.selection_id];
                        if (savedVal !== undefined) {
                            if (typeof savedVal === 'object' && savedVal !== null) {
                                if (savedVal.top !== undefined) adjustedTop = savedVal.top;
                                if (savedVal.left !== undefined) adjustedLeft = savedVal.left;
                            } else {
                                // Backward compatibility
                                adjustedTop = savedVal;
                            }
                        } else {
                            adjustedTop = Math.max(c.naturalTop, lastBottom + minGap);
                        }

                        div.style.top = adjustedTop + 'px';
                        div.style.left = adjustedLeft + 'px';

                        // Draw connector line based on final adjusted top/left
                        if (c.targetElement) {
                            updateConnectorLine(div, c.targetElement);
                        }

                        // Make the comment card draggable in all directions (X and Y)
                        div.style.cursor = 'grab';
                        div.onmousedown = (e) => {
                            // Ignore if clicked on button, input, or inside reply form
                            if (e.target.closest('button, input, textarea, .comment-reply-form')) {
                                return;
                            }
                            
                            e.preventDefault();
                            div.style.cursor = 'grabbing';
                            
                            const startMouseX = e.clientX;
                            const startMouseY = e.clientY;
                            const startTop = parseFloat(div.style.top) || 0;
                            const startLeft = parseFloat(div.style.left) || 0;
                            
                            function onMouseMove(moveEvent) {
                                const deltaX = moveEvent.clientX - startMouseX;
                                const deltaY = moveEvent.clientY - startMouseY;
                                const newTop = startTop + deltaY;
                                const newLeft = startLeft + deltaX;
                                
                                div.style.top = newTop + 'px';
                                div.style.left = newLeft + 'px';
                                
                                // Update connector line in real-time
                                if (c.targetElement) {
                                    updateConnectorLine(div, c.targetElement);
                                }
                                
                                // Save to global session offsets
                                window.manualCommentOffsets[c.selection_id] = {
                                    top: newTop,
                                    left: newLeft
                                };
                            }
                            
                            function onMouseUp() {
                                div.style.cursor = 'grab';
                                document.removeEventListener('mousemove', onMouseMove);
                                document.removeEventListener('mouseup', onMouseUp);
                                
                                // Persist to localStorage on mouseup
                                localStorage.setItem(storageKey, JSON.stringify(window.manualCommentOffsets));
                            }
                            
                            document.addEventListener('mousemove', onMouseMove);
                            document.addEventListener('mouseup', onMouseUp);
                        };

                        lastBottom = adjustedTop + div.offsetHeight;
                    });
                }, 50);

            }, 100);
        };

        // Initial render
        renderComments();

        function syncBlockContentAfterComment(node) {
            if (!node) return;
            
            // Find closest block-item
            const blockEl = node.closest('.block-item');
            if (!blockEl) return;
            
            const blockId = blockEl.getAttribute('data-id');
            const item = items.find(i => i.id === blockId);
            if (!item) return;
            
            // Mark as dirty
            item.dirty = true;
            
            if (item.type === 'static-text') {
                const displayEl = blockEl.querySelector('.static-text-display');
                if (displayEl) {
                    item.content = displayEl.innerHTML;
                }
            } else if (item.type === 'table') {
                const tdEl = node.closest('td');
                if (tdEl) {
                    const rStr = tdEl.getAttribute('data-row');
                    const cStr = tdEl.getAttribute('data-col');
                    if (rStr !== null && cStr !== null) {
                        const r = parseInt(rStr) - 1;
                        const c = parseInt(cStr);
                        if (item.data && item.data[r] && item.data[r][c]) {
                            item.data[r][c].content = tdEl.innerHTML;
                        }
                    }
                }
            }
            
            if (typeof saveStateDebounced === 'function') {
                saveStateDebounced();
            }
        }

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
                    if (result.value) {
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

                        // Sync block content to items array immediately
                        syncBlockContentAfterComment(span);

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

        window.toggleReplyForm = function(e, commentId) {
            e.stopPropagation();
            const form = document.getElementById('reply-form-' + commentId);
            if (form) {
                form.classList.toggle('d-none');
                if (!form.classList.contains('d-none')) {
                    const input = document.getElementById('reply-input-' + commentId);
                    if (input) input.focus();
                }
            }
        };

        window.submitCommentReply = function(commentId) {
            const input = document.getElementById('reply-input-' + commentId);
            if (!input) return;
            const content = input.value.trim();
            if (!content) return;

            $.post('{{ route('pages.ebmr.replyComment') }}', {
                _token: '{{ csrf_token() }}',
                id: commentId,
                content: content
            }, function(res) {
                if (res.success) {
                    // Update local templateComments structure
                    const comment = window.templateComments.find(c => c.id === commentId);
                    if (comment) {
                        let contentData = null;
                        try {
                            contentData = JSON.parse(comment.content);
                        } catch(e) {}
                        
                        if (!contentData || typeof contentData !== 'object' || !contentData.hasOwnProperty('text')) {
                            contentData = {
                                text: comment.content,
                                replies: []
                            };
                        }
                        contentData.replies.push(res.reply);
                        comment.content = JSON.stringify(contentData);
                    }
                    
                    renderComments();
                } else {
                    toastr.error(res.message || 'Không thể gửi phản hồi');
                }
            });
        };

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
                if (result.value) {
                    $.post('{{ route('pages.ebmr.deleteComment') }}', {
                        _token: '{{ csrf_token() }}',
                        id: id
                    }, function(res) {
                        if (res.success) {
                            // Find highlight span and unwrap it
                            const span = document.getElementById(selectionId);
                            if (span) {
                                const parent = span.parentNode;
                                span.replaceWith(...span.childNodes);
                                if (parent) {
                                    syncBlockContentAfterComment(parent);
                                }
                            }
                            
                            // Remove manual offset from memory and localStorage
                            if (window.manualCommentOffsets && window.manualCommentOffsets[selectionId]) {
                                delete window.manualCommentOffsets[selectionId];
                                localStorage.setItem(storageKey, JSON.stringify(window.manualCommentOffsets));
                            }
                            
                            // Remove from local list and re-render
                            window.templateComments = window.templateComments.filter(c => c.id !== id);
                            renderComments();
                        }
                    });
                }
            });
        };

        window.toggleCommentsVisibility = function() {
            window.commentsHidden = !window.commentsHidden;
            localStorage.setItem('ebmr_comments_hidden', window.commentsHidden);
            
            const mainContent = document.getElementById('mainContent');
            const toggleBtn = document.getElementById('btn-toggle-comments');
            
            if (window.commentsHidden) {
                if (mainContent) mainContent.classList.add('hide-comment-highlights');
                if (toggleBtn) {
                    toggleBtn.innerHTML = '<i class="fas fa-eye-slash"></i>';
                    toggleBtn.title = "Hiện bình luận trong văn bản";
                    toggleBtn.classList.add('text-danger');
                }
            } else {
                if (mainContent) mainContent.classList.remove('hide-comment-highlights');
                if (toggleBtn) {
                    toggleBtn.innerHTML = '<i class="fas fa-eye"></i>';
                    toggleBtn.title = "Ẩn bình luận trong văn bản";
                    toggleBtn.classList.remove('text-danger');
                }
            }
            renderComments();
        };

        // Initialize comments visibility on page load
        const mainContent = document.getElementById('mainContent');
        const toggleBtn = document.getElementById('btn-toggle-comments');
        if (window.commentsHidden) {
            if (mainContent) mainContent.classList.add('hide-comment-highlights');
            if (toggleBtn) {
                toggleBtn.innerHTML = '<i class="fas fa-eye-slash"></i>';
                toggleBtn.title = "Hiện bình luận trong văn bản";
                toggleBtn.classList.add('text-danger');
            }
        } else {
            if (mainContent) mainContent.classList.remove('hide-comment-highlights');
            if (toggleBtn) {
                toggleBtn.innerHTML = '<i class="fas fa-eye"></i>';
                toggleBtn.title = "Ẩn bình luận trong văn bản";
                toggleBtn.classList.remove('text-danger');
            }
        }
    });
</script>
