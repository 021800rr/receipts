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

        // Build a quick lookup of receipts scheduled for deletion in this flush
        $scheduledReceiptDeletions = [];
        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            if ($entity instanceof Receipt) {
                $scheduledReceiptDeletions[spl_object_hash($entity)] = true;
            }
        }

        // 1) Collect affected lines and receipts for INSERT/UPDATE/DELETE
        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if ($entity instanceof ReceiptLine) {
                $linesToRecompute[] = $entity; // ensure lineTotal is computed and tracked
                $r = $entity->getReceipt();
                if ($r instanceof Receipt) {
                    $key = spl_object_hash($r);
                    if (!isset($scheduledReceiptDeletions[$key])) {
                        $affectedReceipts[$key] = $r;
                    }
                }
            }
        }
        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if ($entity instanceof ReceiptLine) {
                $linesToRecompute[] = $entity; // ensure lineTotal is recomputed and tracked
                $r = $entity->getReceipt();
                if ($r instanceof Receipt) {
                    $key = spl_object_hash($r);
                    if (!isset($scheduledReceiptDeletions[$key])) {
                        $affectedReceipts[$key] = $r;
                    }
                }
            }
        }
        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            if ($entity instanceof ReceiptLine) {
                $r = $entity->getReceipt();
                if ($r instanceof Receipt) {
                    $key = spl_object_hash($r);
                    // Only recalc if the parent receipt itself is NOT being deleted
                    if (!isset($scheduledReceiptDeletions[$key])) {
                        $affectedReceipts[$key] = $r;
                    }
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
                // Skip lines that are being deleted (no need to recompute)
                if (!isset($scheduledReceiptDeletions[spl_object_hash($line->getReceipt())])) {
                    $uow->recomputeSingleEntityChangeSet($lineMeta, $line);
                }
            }
        }

        if (!$affectedReceipts) {
            return; // only line changes without a parent? nothing more to do
        }

        // 3) Recalculate and mark receipts as changed so Doctrine issues UPDATEs
        $receiptMeta = $em->getClassMetadata(Receipt::class);
        foreach ($affectedReceipts as $receipt) {
            // Recompute only for MANAGED receipts which are not being deleted in this flush
            $state = $uow->getEntityState($receipt);
            if ($state !== \Doctrine\ORM\UnitOfWork::STATE_MANAGED) {
                continue;
            }
            if (isset($scheduledReceiptDeletions[spl_object_hash($receipt)])) {
                continue;
            }
            $receipt->recalc();
            $uow->recomputeSingleEntityChangeSet($receiptMeta, $receipt);
        }
    }
}
