import Modal from '@typo3/backend/modal.js';
import Severity from '@typo3/backend/severity.js';

/**
 * Sets up click handlers on all .t3js-blogsync-delete buttons.
 * Called on initial page load and on TYPO3 SPA module navigation.
 */
function setupDeleteHandlers() {
    document.querySelectorAll('.t3js-blogsync-delete').forEach((btn) => {
        if (btn.dataset.blogsyncHandlerAttached) {
            return;
        }
        btn.dataset.blogsyncHandlerAttached = '1';

        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const deleteUrl = btn.dataset.deleteUrl;

            const modal = Modal.confirm(
                btn.dataset.confirmTitle,
                btn.dataset.confirmBody,
                Severity.warning,
                [
                    {
                        text: btn.dataset.confirmCancel,
                        active: true,
                        btnClass: 'btn-default',
                        trigger: () => modal.hideModal(),
                    },
                    {
                        text: btn.dataset.confirmDelete,
                        btnClass: 'btn-danger',
                        trigger: () => { modal.hideModal(); window.location.href = deleteUrl; },
                    },
                ]
            );
        });
    });
}

// Run on initial load and after TYPO3 SPA navigates to this module
document.addEventListener('typo3:module:load', setupDeleteHandlers);
setupDeleteHandlers();
