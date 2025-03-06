            </div><!-- End container-fluid -->
        </div><!-- End main-content -->
    </div><!-- End wrapper -->

    <!-- Common Modal -->
    <div class="modal fade" id="commonModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="commonModalTitle"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p id="commonModalBody"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Common JavaScript functions
        function showModal(title, message, type = 'info') {
            const modal = new bootstrap.Modal(document.getElementById('commonModal'));
            const modalTitle = document.getElementById('commonModalTitle');
            const modalBody = document.getElementById('commonModalBody');
            
            modalTitle.textContent = title;
            modalBody.textContent = message;
            
            const modalDialog = modal._element.querySelector('.modal-dialog');
            modalDialog.classList.remove('modal-danger', 'modal-success', 'modal-warning', 'modal-info');
            modalDialog.classList.add(`modal-${type}`);
            
            modal.show();
        }

        // Initialize all tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    </script>
</body>
</html> 