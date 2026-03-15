</div>
        </main>
    </div>
    
    <!-- Modals -->
    <div id="modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modal-title">Modal Title</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body" id="modal-body">
                <!-- Dynamic content -->
            </div>
        </div>
    </div>
    
    <script src="assets/js/main.js"></script>
    <?php if (isset($additional_js)): ?>
        <script><?php echo $additional_js; ?></script>
    <?php endif; ?>
</body>
</html>
