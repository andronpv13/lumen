<?php
// modules/404.php - Страница ошибки 404
$pageTitle = 'Страница не найдена';
?>

<div class="error-page">
  <h1>404</h1>
  <h2>Страница не найдена</h2>
  <p>К сожалению, запрашиваемая страница не существует или была удалена.</p>
  <div class="error-actions">
    <a href="/?route=shop" class="btn btn-ghost">Вернуться в каталог</a>
    <a href="/" class="btn btn-ghost">На главную</a>
  </div>
</div>

<style>
.error-page {
  text-align: center;
  padding: 4rem 2rem;
  max-width: 600px;
  margin: 0 auto;
}

.error-page h1 {
  font-size: 8rem;
  font-weight: bold;
  color: #e74c3c;
  margin: 0;
  line-height: 1;
}

.error-page h2 {
  font-size: 2rem;
  margin: 1rem 0;
  color: #2c3e50;
}

.error-page p {
  font-size: 1.1rem;
  color: #7f8c8d;
  margin-bottom: 2rem;
}

.error-actions {
  display: flex;
  gap: 1rem;
  justify-content: center;
  flex-wrap: wrap;
}
</style>