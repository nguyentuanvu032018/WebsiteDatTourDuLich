<?php $this->layout('layouts/auth', ['title' => 'Đăng Ký - VivuTour']) ?>

<?php $this->start('page') ?>
<div class="container">
  <div class="row justify-content-center">
    <div class="col-12 col-sm-10 col-md-8 col-lg-6 col-xl-5">

      <!-- Card -->
      <div class="card border-0 shadow rounded-4 p-4">

        <!-- Tiêu đề -->
        <div class="text-center mb-4">
          <span class="display-6 text-warning"><i class="bi bi-person-plus"></i></span>
          <h4 class="fw-bold mt-2 mb-1">Tạo tài khoản</h4>
          <p class="text-muted small mb-0">Đăng ký để bắt đầu hành trình cùng VivuTour</p>
        </div>

        <!-- Form đăng ký -->
        <form action="/register" method="POST" novalidate>
          <?= csrf_field() ?>

          <!-- Họ tên -->
          <div class="mb-3">
            <label for="regName" class="form-label fw-semibold small">
              Họ và tên <span class="text-danger">*</span>
            </label>
            <div class="input-group">
              <span class="input-group-text bg-light">
                <i class="bi bi-person text-secondary"></i>
              </span>
              <input type="text" id="regName" name="name"
                class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                placeholder="Nguyễn Văn A"
                value="<?= $this->e($old['name'] ?? '') ?>"
                required autofocus />
              <?php if (isset($errors['name'])) : ?>
                <div class="invalid-feedback"><?= $this->e($errors['name']) ?></div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Email -->
          <div class="mb-3">
            <label for="regEmail" class="form-label fw-semibold small">
              Email <span class="text-danger">*</span>
            </label>
            <div class="input-group">
              <span class="input-group-text bg-light">
                <i class="bi bi-envelope text-secondary"></i>
              </span>
              <input type="email" id="regEmail" name="email"
                class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                placeholder="name@example.com"
                value="<?= $this->e($old['email'] ?? '') ?>"
                required />
              <?php if (isset($errors['email'])) : ?>
                <div class="invalid-feedback"><?= $this->e($errors['email']) ?></div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Số điện thoại -->
          <div class="mb-3">
            <label for="regPhone" class="form-label fw-semibold small">
              Số điện thoại <span class="text-danger">*</span>
            </label>
            <div class="input-group">
              <span class="input-group-text bg-light">
                <i class="bi bi-telephone text-secondary"></i>
              </span>
              <input type="tel" id="regPhone" name="sDT"
                class="form-control <?= isset($errors['sDT']) ? 'is-invalid' : '' ?>"
                placeholder="0912 345 678"
                value="<?= $this->e($old['sDT'] ?? '') ?>"
                required />
              <?php if (isset($errors['sDT'])) : ?>
                <div class="invalid-feedback"><?= $this->e($errors['sDT']) ?></div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Địa chỉ -->
          <div class="mb-3">
            <label for="regAddress" class="form-label fw-semibold small">
              Địa chỉ
            </label>
            <div class="input-group">
              <span class="input-group-text bg-light">
                <i class="bi bi-geo-alt text-secondary"></i>
              </span>
              <input type="text" id="regAddress" name="address"
                class="form-control"
                placeholder="Số nhà, Đường, Quận/Huyện, Tỉnh"
                value="<?= $this->e($old['address'] ?? '') ?>" />
            </div>
          </div>

          <!-- Mật khẩu -->
          <div class="mb-3">
            <label for="regPassword" class="form-label fw-semibold small">
              Mật khẩu <span class="text-danger">*</span>
            </label>
            <div class="input-group">
              <span class="input-group-text bg-light">
                <i class="bi bi-lock text-secondary"></i>
              </span>
              <input type="password" id="regPassword" name="password"
                class="form-control border-end-0 <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                placeholder="Tối thiếu 6 ký tự" required />
              <button class="btn btn-outline-secondary border-start-0" type="button"
                onclick="togglePwd('regPassword', 'eye1')">
                <i id="eye1" class="bi bi-eye"></i>
              </button>
              <?php if (isset($errors['password'])) : ?>
                <div class="invalid-feedback d-block"><?= $this->e($errors['password']) ?></div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Nhập lại mật khẩu -->
          <div class="mb-4">
            <label for="regConfirm" class="form-label fw-semibold small">
              Nhập lại mật khẩu <span class="text-danger">*</span>
            </label>
            <div class="input-group">
              <span class="input-group-text bg-light">
                <i class="bi bi-lock-fill text-secondary"></i>
              </span>
              <input type="password" id="regConfirm" name="password_confirmation"
                class="form-control border-end-0 <?= isset($errors['password_confirmation']) ? 'is-invalid' : '' ?>"
                placeholder="Nhập lại mật khẩu" required />
              <button class="btn btn-outline-secondary border-start-0" type="button"
                onclick="togglePwd('regConfirm', 'eye2')">
                <i id="eye2" class="bi bi-eye"></i>
              </button>
              <?php if (isset($errors['password_confirmation'])) : ?>
                <div class="invalid-feedback d-block"><?= $this->e($errors['password_confirmation']) ?></div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Nút đăng ký -->
          <div class="d-grid mb-3">
            <button type="submit" class="btn btn-warning fw-bold py-2 text-white">
              <i class="bi bi-person-check me-1"></i> Đăng ký tài khoản
            </button>
          </div>

          <div class="text-center small">
            <span class="text-muted">Đã có tài khoản?</span>
            <a href="/login" class="fw-semibold text-warning text-decoration-none ms-1">Đăng nhập ngay</a>
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
