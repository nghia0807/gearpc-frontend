<?php
/**
 * Confirmation Modal Component
 * 
 * This component provides a reusable Bootstrap modal for confirmation dialogs,
 * replacing the default browser alerts/confirms with a styled modal.
 * 
 * Usage:
 * 1. Include this file: include 'components/confirm-modal.php';
 * 2. Call renderConfirmModal() where you want to include the modal in your HTML
 * 3. Use showConfirmModal() JavaScript function to display the modal with custom message and callback
 */

/**
 * Renders the confirmation modal HTML
 * 
 * @param string $id Modal ID (must be unique on the page)
 * @param string $title Default modal title
 * @param string $confirmBtnText Text for confirm/yes button
 * @param string $cancelBtnText Text for cancel/no button
 * @param string $confirmBtnClass Bootstrap class for confirm button
 * @param string $cancelBtnClass Bootstrap class for cancel button
 */
function renderConfirmModal(
    string $id = 'confirmModal',
    string $title = 'Confirmation',
    string $confirmBtnText = 'Confirm',
    string $cancelBtnText = 'Cancel',
    string $confirmBtnClass = 'btn-danger',
    string $cancelBtnClass = 'btn-secondary'
) {
    ?>
    <!-- Confirmation Modal -->
    <div class="modal fade" id="<?= htmlspecialchars($id) ?>" tabindex="-1"
        aria-labelledby="<?= htmlspecialchars($id) ?>Label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="<?= htmlspecialchars($id) ?>Label"><?= htmlspecialchars($title) ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="<?= htmlspecialchars($id) ?>Message"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn <?= htmlspecialchars($cancelBtnClass) ?>"
                        data-bs-dismiss="modal"><?= htmlspecialchars($cancelBtnText) ?></button>
                    <button type="button" class="btn <?= htmlspecialchars($confirmBtnClass) ?>"
                        id="<?= htmlspecialchars($id) ?>ConfirmBtn"><?= htmlspecialchars($confirmBtnText) ?></button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Store modal instance
            const <?= $id ?>Element = document.getElementById('<?= htmlspecialchars($id) ?>');
            const <?= $id ?>Instance = new bootstrap.Modal(<?= $id ?>Element);

            // Store current callback
            let currentCallback = null;

            // Handle confirmation
            document.getElementById('<?= htmlspecialchars($id) ?>ConfirmBtn').addEventListener('click', function () {
                if (typeof currentCallback === 'function') {
                    currentCallback(true);
                }
                    <?= $id ?>Instance.hide();
            });

                // Handle dismissal (when clicking the x or cancel button)
                <?= $id ?>Element.addEventListener('hidden.bs.modal', function () {
                if (typeof currentCallback === 'function') {
                    currentCallback(false);
                    currentCallback = null;
                }
            });

            // Make function available globally
            window.showConfirmModal = function (message, callback, title = null) {
                // Update modal content
                document.getElementById('<?= htmlspecialchars($id) ?>Message').textContent = message;

                // Update title if provided
                if (title) {
                    document.getElementById('<?= htmlspecialchars($id) ?>Label').textContent = title;
                }

                // Store callback
                currentCallback = callback;

                    // Show modal
                    <?= $id ?>Instance.show();
            };
        });
    </script>
    <?php
}
?>