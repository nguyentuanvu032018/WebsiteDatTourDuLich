<?php $this->layout('layouts/auth', ['title' => 'Đăng Nhập - VivuTour']) ?>

<?php $this->start('page') ?>
<div class="container">
  <div class="row justify-content-center">
    <div class="col-12 col-sm-9 col-md-7 col-lg-5 col-xl-4">

      <!-- Card -->
      <div class="card border-0 shadow rounded-4 p-4">

        <!-- Tiêu đề -->
        <div class="text-center mb-4">
          <span class="display-6 text-warning"><i class="bi bi-airplane-engines"></i></span>
          <h4 class="fw-bold mt-2 mb-1">Đăng nhập</h4>
          <p class="text-muted small mb-0">Chào mừng bạn trở lại VivuTour!</p>
        </div>

        <!-- Thông báo lỗi đăng nhập -->
        <?php if (!empty($errors['login'] ?? '')) : ?>
          <div class="alert alert-danger d-flex align-items-center gap-2 py-2 small" role="alert">
            <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i>
            <span><?= $this->e($errors['login']) ?></span>
          </div>
        <?php endif; ?>

        <!-- Thành công (sau khi đăng ký) -->
        <?php if (!empty($flashSuccess ?? '')) : ?>
          <div class="alert alert-success d-flex align-items-center gap-2 py-2 small" role="alert">
            <i class="bi bi-check-circle-fill flex-shrink-0"></i>
            <span><?= $this->e($flashSuccess) ?></span>
          </div>
        <?php endif; ?>

        <!-- Form đăng nhập -->
        <form action="/login" method="POST" novalidate>
          <?= csrf_field() ?>

          <!-- Email -->
          <div class="mb-3">
            <label for="loginEmail" class="form-label fw-semibold small">
              Email <span class="text-danger">*</span>
            </label>
            <div class="input-group">
              <span class="input-group-text bg-light">
                <i class="bi bi-envelope text-secondary"></i>
              </span>
              <input type="email" id="loginEmail" name="email"
                class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                placeholder="name@example.com"
                value="<?= $this->e($old['email'] ?? '') ?>"
                required autofocus />
              <?php if (isset($errors['email'])) : ?>
                <div class="invalid-feedback"><?= $this->e($errors['email']) ?></div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Mật khẩu -->
          <div class="mb-4">
            <label for="loginPassword" class="form-label fw-semibold small">
              Mật khẩu <span class="text-danger">*</span>
            </label>
            <div class="input-group">
              <span class="input-group-text bg-light">
                <i class="bi bi-lock text-secondary"></i>
              </span>
              <input type="password" id="loginPassword" name="password"
                class="form-control border-end-0 <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                placeholder="Nhập mật khẩu" required />
              <button class="btn btn-outline-secondary border-start-0" type="button"
                onclick="togglePwd('loginPassword','eyeIcon1')">
                <i id="eyeIcon1" class="bi bi-eye"></i>
              </button>
              <?php if (isset($errors['password'])) : ?>
                <div class="invalid-feedback"><?= $this->e($errors['password']) ?></div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Nút đăng nhập -->
          <div class="d-grid mb-3">
            <button type="submit" class="btn btn-warning fw-bold py-2 text-white">
              <i class="bi bi-box-arrow-in-right me-1"></i> Đăng nhập
            </button>
          </div>

          <div class="text-center small">
            <span class="text-muted">Chưa có tài khoản?</span>
            <a href="/register" class="fw-semibold text-warning text-decoration-none ms-1">Đăng ký ngay</a>
          </div>
        </form>

      </div><!-- /card -->
    </div>
  </div>
</div>

<script>
function togglePwd(id, iconId) {
  const el = document.getElementById(id);
  const ic = document.getElementById(iconId);
  if (el.type === 'password') {
    el.type = 'text';
    ic.className = 'bi bi-eye-slash';
  } else {
    el.type = 'password';
    ic.className = 'bi bi-eye';
  }
}
</script>
<?php $this->stop() ?>
