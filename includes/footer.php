            </div><!-- /.container-fluid -->
        </main><!-- /.content-body -->

        <?php if (is_logged_in() && (!isset($hideFooter) || !$hideFooter)): ?>
        <footer class="py-3 px-4 bg-white border-top text-center text-muted small mt-auto">
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
                <div>
                    <strong>Student Expense Tracker</strong> &bull; DBMS Project
                </div>
                <div>
                    Built with PHP, MySQL &amp; Bootstrap 5 &bull; Normalized Relational Schema
                </div>
            </div>
        </footer>
        <?php endif; ?>
    </div><!-- /.main-content -->
</div><!-- /.app-wrapper -->

<!-- Bootstrap 5.3 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom App JS -->
<script src="<?= $baseUrl ?>/assets/js/script.js"></script>

</body>
</html>
