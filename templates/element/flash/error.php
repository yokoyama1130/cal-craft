<?php
/**
 * @var string $message フラッシュメッセージ本文
 * @var string $key     フラッシュの種類（success, error, warning, info など）
 */
if (!isset($params['escape']) || $params['escape'] !== false) {
    $message = h($message);
}

$classes = [
    'flash' => true,
    'alert' => true,
    'mt-3' => true,
    'shadow-sm' => true,
];
switch ($key) {
    case 'success':
        $classes['alert-success'] = true;
        $icon = '<i class="fa-solid fa-circle-check me-2"></i>';
        break;
    case 'error':
        $classes['alert-danger'] = true;
        $icon = '<i class="fa-solid fa-triangle-exclamation me-2"></i>';
        break;
    case 'warning':
        $classes['alert-warning'] = true;
        $icon = '<i class="fa-solid fa-circle-exclamation me-2"></i>';
        break;
    default:
        $classes['alert-info'] = true;
        $icon = '<i class="fa-solid fa-circle-info me-2"></i>';
        break;
}
?>
<div 
    class="<?= implode(' ', array_keys(array_filter($classes))) ?> 
           d-flex align-items-center justify-content-between" 
    role="alert"
>
    <div><?= $message ?></div>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="閉じる"></button>
</div>