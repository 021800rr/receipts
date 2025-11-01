<?php

namespace App\EventSubscriber;

use App\Entity\Receipt;
use App\Entity\ReceiptLine;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;

/**
 * Keeps Receipt.totalAmount in sync when ReceiptLine rows are inserted/updated/removed.
 *
 * We use onFlush to ensure the parent Receipt is marked dirty within the same flush
 * and Doctrine includes the UPDATE statement for the receipt in the current transaction.
 */
final class ReceiptTotalsSubscriber implements EventSubscriber
{
    public function getSubscribedEvents(): array
    {
        return [
            Events::onFlush,
        ];
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $em = $args->getObjectManager();
        $uow = $em->getUnitOfWork();

        $affectedReceipts = [];
        $linesToRecompute = [];

        // 1) Collect affected lines and receipts for INSERT/UPDATE/DELETE
        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if ($entity instanceof ReceiptLine) {
                $linesToRecompute[] = $entity; // ensure lineTotal is computed and tracked
                $r = $entity->getReceipt();
                if ($r instanceof Receipt) {
                    $affectedReceipts[spl_object_hash($r)] = $r;
                }
            }
        }
        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if ($entity instanceof ReceiptLine) {
                $linesToRecompute[] = $entity; // ensure lineTotal is recomputed and tracked
                $r = $entity->getReceipt();
                if ($r instanceof Receipt) {
                    $affectedReceipts[spl_object_hash($r)] = $r;
                }
            }
        }
        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            if ($entity instanceof ReceiptLine) {
                $r = $entity->getReceipt();
                if ($r instanceof Receipt) {
                    $affectedReceipts[spl_object_hash($r)] = $r;
                }
            }
        }

        if (!$affectedReceipts && !$linesToRecompute) {
            return;
        }

        // 2) Make sure ReceiptLine.lineTotal changes are included in current flush
        if ($linesToRecompute) {
            $lineMeta = $em->getClassMetadata(ReceiptLine::class);
            foreach ($linesToRecompute as $line) {
                // recompute line total using current state
                $line->computeLineTotal();
                // and ensure Doctrine tracks this field change in the same flush
                $uow->recomputeSingleEntityChangeSet($lineMeta, $line);
            }
        }

        if (!$affectedReceipts) {
            return; // only line changes without a parent? nothing more to do
        }

        // 3) Recalculate and mark receipts as changed so Doctrine issues UPDATEs
        $receiptMeta = $em->getClassMetadata(Receipt::class);
        foreach ($affectedReceipts as $receipt) {
            $receipt->recalc();
            $uow->recomputeSingleEntityChangeSet($receiptMeta, $receipt);
        }
    }
}
