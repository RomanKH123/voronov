<?php
declare(strict_types=1);

function saveWorkOrder(PDO $pdo, $ids): void
{
    if (!is_array($ids) || !$ids || array_values($ids) !== $ids) {
        throw new InvalidArgumentException('Expected a non-empty list');
    }
    foreach ($ids as $id) {
        if (!is_int($id) || $id < 1) {
            throw new InvalidArgumentException('Invalid work ID');
        }
    }
    if (count(array_unique($ids)) !== count($ids)) {
        throw new InvalidArgumentException('Duplicate work IDs');
    }

    $pdo->beginTransaction();
    try {
        // Lock a consistent snapshot; reject stale lists after additions/deletions.
        $lock = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql' ? ' FOR UPDATE' : '';
        $existing = array_map('intval', $pdo->query('SELECT id FROM works ORDER BY id' . $lock)->fetchAll(PDO::FETCH_COLUMN));
        $submitted = $ids;
        sort($submitted, SORT_NUMERIC);
        if ($submitted !== $existing) {
            throw new UnexpectedValueException('Work list changed');
        }
        $stmt = $pdo->prepare('UPDATE works SET sort_order = :position WHERE id = :id');
        foreach ($ids as $index => $id) {
            $stmt->execute([':position' => $index + 1, ':id' => $id]);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}
