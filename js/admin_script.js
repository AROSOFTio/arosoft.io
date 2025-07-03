// WordPress-like Admin Panel JavaScript

// Mobile sidebar toggle
document.addEventListener('DOMContentLoaded', function() {
    const sidebarToggle = document.getElementById('adminSidebarToggle');
    const sidebar = document.getElementById('adminSidebar');
    const overlay = document.getElementById('sidebarOverlay');

    if (sidebarToggle && sidebar && overlay) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('hidden');
            sidebar.classList.toggle('-translate-x-full');
            overlay.classList.toggle('hidden');
        });

        overlay.addEventListener('click', function() {
            sidebar.classList.add('hidden');
            sidebar.classList.add('-translate-x-full');
            overlay.classList.add('hidden');
        });
    }
});

// Post Editor Enhancements
if (document.getElementById('post_content_editor')) {
    // Enhanced TinyMCE Configuration
    tinymce.init({
        selector: '#post_content_editor',
        plugins: [
            'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
            'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
            'insertdatetime', 'media', 'table', 'help', 'wordcount', 'autoresize',
            'paste', 'emoticons', 'codesample'
        ],
        toolbar: [
            'undo redo | formatselect | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify',
            'bullist numlist | outdent indent | link image media | forecolor backcolor emoticons | code preview fullscreen',
            'table | hr removeformat | subscript superscript | charmap | codesample'
        ],
        menubar: 'file edit view insert format tools table help',
        toolbar_mode: 'sliding',
        contextmenu: 'link image table',
        content_style: `
            body {
                font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen-Sans, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif;
                font-size: 16px;
                line-height: 1.6;
                color: #1f2937;
                margin: 1rem;
            }
            p { margin: 0 0 1em; }
            table { border-collapse: collapse; }
            table td, table th { border: 1px solid #e5e7eb; padding: 0.5rem; }
        `,
        height: 600,
        min_height: 400,
        max_height: 800,
        autoresize_bottom_margin: 50,
        relative_urls: false,
        remove_script_host: false,
        convert_urls: true,
        browser_spellcheck: true,
        paste_data_images: true,
        image_advtab: true,
        link_context_toolbar: true,
        setup: function (editor) {
            // Auto-save functionality
            let timer;
            editor.on('change keyup', function() {
                clearTimeout(timer);
                timer = setTimeout(function() {
                    editor.save();
                    // TODO: Implement auto-save to server
                    console.log('Content auto-saved locally');
                }, 3000);
            });

            // Warn before leaving with unsaved changes
            window.onbeforeunload = function() {
                if (editor.isDirty()) {
                    return 'You have unsaved changes. Do you really want to leave?';
                }
            };
        }
    });
}

// Character Counter for Meta Description
const metaDescTextarea = document.getElementById('post_meta_description');
const metaDescCharCount = document.getElementById('meta_desc_char_count');
if (metaDescTextarea && metaDescCharCount) {
    function updateMetaDescCount() {
        const count = metaDescTextarea.value.length;
        metaDescCharCount.textContent = count;
        
        // Visual feedback
        if (count > 155) {
            metaDescCharCount.classList.add('text-yellow-600');
        } else {
            metaDescCharCount.classList.remove('text-yellow-600');
        }
        if (count > 160) {
            metaDescCharCount.classList.add('text-red-600');
        } else {
            metaDescCharCount.classList.remove('text-red-600');
        }
    }
    
    metaDescTextarea.addEventListener('input', updateMetaDescCount);
    updateMetaDescCount();
}

// Featured Image Preview
const featuredImageInput = document.getElementById('featured_image');
if (featuredImageInput) {
    featuredImageInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            if (file.size > 2 * 1024 * 1024) { // 2MB
                alert('File is too large. Maximum size is 2MB.');
                e.target.value = '';
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(e) {
                const preview = document.querySelector('.featured-image-preview');
                if (preview) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
            };
            reader.readAsDataURL(file);
        }
    });
}

// Slug Generator
const titleInput = document.getElementById('post_title');
const slugInput = document.getElementById('post_slug');
if (titleInput && slugInput) {
    titleInput.addEventListener('input', function() {
        if (!slugInput.value || slugInput.dataset.autoGenerated === 'true') {
            slugInput.value = slugify(this.value);
            slugInput.dataset.autoGenerated = 'true';
        }
    });
    
    slugInput.addEventListener('input', function() {
        this.dataset.autoGenerated = 'false';
    });
}

function slugify(text) {
    return text.toString().toLowerCase()
        .replace(/\s+/g, '-')
        .replace(/[^\w\-]+/g, '')
        .replace(/\-\-+/g, '-')
        .replace(/^-+/, '')
        .replace(/-+$/, '');
}

// Comment Management Functions
function showModal(title, message, actionCallback) {
    const modal = document.getElementById('confirmationModal');
    if (modal) {
        document.getElementById('modal-title').textContent = title;
        document.getElementById('modal-message').textContent = message;
        document.getElementById('confirmButton').onclick = () => {
            closeModal();
            actionCallback();
        };
        modal.classList.remove('hidden');
    }
}

function closeModal() {
    const modal = document.getElementById('confirmationModal');
    if (modal) {
        modal.classList.add('hidden');
    }
}

function approveComment(commentId) {
    showModal(
        'Approve Comment',
        'Are you sure you want to approve this comment?',
        () => window.location.href = `${adminBaseUrl}actions/approve_comment.php?id=${commentId}&token=${csrfToken}`
    );
}

function markAsSpam(commentId) {
    showModal(
        'Mark as Spam',
        'Are you sure you want to mark this comment as spam?',
        () => window.location.href = `${adminBaseUrl}actions/mark_comment_spam.php?id=${commentId}&token=${csrfToken}`
    );
}

function moveToTrash(commentId) {
    showModal(
        'Move to Trash',
        'Are you sure you want to move this comment to trash?',
        () => window.location.href = `${adminBaseUrl}actions/trash_comment.php?id=${commentId}&token=${csrfToken}`
    );
}

function deleteComment(commentId) {
    showModal(
        'Delete Comment',
        'Are you sure you want to permanently delete this comment? This action cannot be undone.',
        () => window.location.href = `${adminBaseUrl}actions/delete_comment.php?id=${commentId}&token=${csrfToken}`
    );
}

function restoreComment(commentId) {
    showModal(
        'Restore Comment',
        'Are you sure you want to restore this comment from trash?',
        () => window.location.href = `${adminBaseUrl}actions/restore_comment.php?id=${commentId}&token=${csrfToken}`
    );
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('confirmationModal');
    if (event.target === modal) {
        closeModal();
    }
}

// Initialize Lucide icons
if (typeof lucide !== 'undefined') {
    lucide.createIcons();
}
