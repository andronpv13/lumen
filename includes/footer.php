<?php
// includes/footer.php
?>
</main>
<footer class="site-footer">
  <div class="container footer-inner">
    <div>
      <strong><?= e(setting('shop_name','Lumen')) ?></strong>
      <p>Восковые свечи ручной работы</p>
    </div>
    <div>
      <p>📞 <?= e(setting('shop_phone','')) ?></p>
      <p>✉️ <?= e(setting('shop_email','')) ?></p>
      <p>📍 <?= e(setting('shop_address','')) ?></p>
    </div>
    <div>
      <p>&copy; <?= date('Y') ?> Lumen. Все права защищены.</p>
    </div>
  </div>
</footer>
<script src="/assets/js/main.js"></script>
</body>
</html>