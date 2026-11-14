<?php require __DIR__ . '/../layouts/header.php'; ?>
<?php require __DIR__ . '/../layouts/menu.php'; ?>

<div class="page-heading"><div><p class="eyebrow">ADMINISTRACIÓN</p><h1>Usuarios</h1><p class="muted">Cuentas, roles y estado de acceso.</p></div>
<?php if (RolePermissions::allows((string) ($_SESSION['auth']['role'] ?? ''), 'users.write')): ?><a class="btn btn-primary" href="?route=users.create">＋ Registrar usuario</a><?php endif; ?></div>

<?php if (!empty($message)): ?>
    <div class="alert-error">
        <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<?php if (!empty($success)): ?>
    <div class="alert-success">
        <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<?php if ($page->total() === 0): ?>
    <p>No hay usuarios registrados todavía.</p>
<?php else: ?>
    <div class="table-scroll"><table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Correo</th>
                <th>Rol</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($page->users() as $user): ?>
                <tr>
                    <td class="col-id"><?= htmlspecialchars($user->getId(), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($user->getName(), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($user->getEmail(), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($user->getRole(), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($user->getStatus(), ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="col-actions">
                        <a class="btn btn-sm" href="?route=users.show&amp;id=<?= urlencode($user->getId()) ?>">Ver</a>
                        <?php if (RolePermissions::allows((string) ($_SESSION['auth']['role'] ?? ''), 'users.write')): ?>
                            <a class="btn btn-warning btn-sm"
                                href="?route=users.edit&amp;id=<?= urlencode($user->getId()) ?>">Editar</a>
                        <form method="POST" action="?route=users.delete" style="display:inline;"
                            onsubmit="return confirmDelete(this);"
                            data-name="<?= htmlspecialchars($user->getName(), ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Security::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="id" value="<?= htmlspecialchars($user->getId(), ENT_QUOTES, 'UTF-8') ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table></div>
    <nav class="pagination" aria-label="Paginación de usuarios">
        <span><?= number_format($page->total()) ?> usuarios · Página <?= $page->page() ?> de <?= $page->pages() ?></span>
        <?php if ($page->page() > 1): ?><a class="btn btn-quiet" href="?route=users.index&amp;page=<?= $page->page() - 1 ?>">Anterior</a><?php endif; ?>
        <?php if ($page->page() < $page->pages()): ?><a class="btn btn-quiet" href="?route=users.index&amp;page=<?= $page->page() + 1 ?>">Siguiente</a><?php endif; ?>
    </nav>
<?php endif; ?>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
