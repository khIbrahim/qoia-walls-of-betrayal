<?php

namespace fenomeno\WallsOfBetrayal\Class\Store;

use JsonSerializable;

final class StoreTransaction implements JsonSerializable {

    public function __construct(
        private readonly int $id,
        private readonly string $transactionUuid,
        private readonly string $storeId,
        private readonly string $memberUuid,
        private readonly ?string $customerUuid,
        private string $transactionType, // sale, refund, void
        private float $totalAmount,
        private float $commissionAmount,
        private array $itemsSold,
        private string $paymentMethod, // cash, card, digital, credit
        private ?int $shiftId = null,
        private string $notes = '',
        private int $createdAt = 0
    ) {
        if ($this->createdAt === 0) {
            $this->createdAt = time();
        }
    }

    // Getters
    public function getId(): int { return $this->id; }
    public function getTransactionUuid(): string { return $this->transactionUuid; }
    public function getStoreId(): string { return $this->storeId; }
    public function getMemberUuid(): string { return $this->memberUuid; }
    public function getCustomerUuid(): ?string { return $this->customerUuid; }
    public function getTransactionType(): string { return $this->transactionType; }
    public function getTotalAmount(): float { return $this->totalAmount; }
    public function getCommissionAmount(): float { return $this->commissionAmount; }
    public function getItemsSold(): array { return $this->itemsSold; }
    public function getPaymentMethod(): string { return $this->paymentMethod; }
    public function getShiftId(): ?int { return $this->shiftId; }
    public function getNotes(): string { return $this->notes; }
    public function getCreatedAt(): int { return $this->createdAt; }

    // Setters
    public function setTransactionType(string $type): void {
        $this->transactionType = $type;
    }

    public function voidTransaction(): void {
        $this->transactionType = 'void';
    }

    public function setNotes(string $notes): void {
        $this->notes = $notes;
    }

    // Utility methods
    public function isSale(): bool {
        return $this->transactionType === 'sale';
    }

    public function isRefund(): bool {
        return $this->transactionType === 'refund';
    }

    public function isVoid(): bool {
        return $this->transactionType === 'void';
    }

    public function getItemCount(): int {
        return array_sum(array_column($this->itemsSold, 'quantity'));
    }

    public function getUniqueItemsCount(): int {
        return count($this->itemsSold);
    }

    public function getAverageItemPrice(): float {
        $itemCount = $this->getItemCount();
        return $itemCount > 0 ? $this->totalAmount / $itemCount : 0;
    }

    public function hasItem(string $itemId): bool {
        return array_filter($this->itemsSold, fn($item) => $item['id'] === $itemId) !== [];
    }

    public function getItemQuantity(string $itemId): int {
        $items = array_filter($this->itemsSold, fn($item) => $item['id'] === $itemId);
        return !empty($items) ? array_values($items)[0]['quantity'] : 0;
    }

    public function getDiscountAmount(): float {
        $fullPrice = array_sum(array_map(
            fn($item) => $item['quantity'] * $item['original_price'],
            $this->itemsSold
        ));
        return max(0, $fullPrice - $this->totalAmount);
    }

    public function getDiscountPercentage(): float {
        $fullPrice = array_sum(array_map(
            fn($item) => $item['quantity'] * $item['original_price'],
            $this->itemsSold
        ));
        return $fullPrice > 0 ? ($this->getDiscountAmount() / $fullPrice) * 100 : 0;
    }

    public function getPaymentMethodDisplay(): string {
        return match($this->paymentMethod) {
            'cash' => 'Cash',
            'card' => 'Credit/Debit Card',
            'digital' => 'Digital Payment',
            'credit' => 'Store Credit',
            default => ucfirst($this->paymentMethod)
        };
    }

    public function jsonSerialize(): array {
        return [
            'id' => $this->id,
            'transaction_uuid' => $this->transactionUuid,
            'store_id' => $this->storeId,
            'member_uuid' => $this->memberUuid,
            'customer_uuid' => $this->customerUuid,
            'transaction_type' => $this->transactionType,
            'total_amount' => $this->totalAmount,
            'commission_amount' => $this->commissionAmount,
            'items_sold' => $this->itemsSold,
            'payment_method' => $this->paymentMethod,
            'payment_method_display' => $this->getPaymentMethodDisplay(),
            'shift_id' => $this->shiftId,
            'notes' => $this->notes,
            'item_count' => $this->getItemCount(),
            'unique_items_count' => $this->getUniqueItemsCount(),
            'average_item_price' => $this->getAverageItemPrice(),
            'discount_amount' => $this->getDiscountAmount(),
            'discount_percentage' => $this->getDiscountPercentage(),
            'is_sale' => $this->isSale(),
            'is_refund' => $this->isRefund(),
            'is_void' => $this->isVoid(),
            'created_at' => $this->createdAt,
            'created_at_formatted' => date('Y-m-d H:i:s', $this->createdAt)
        ];
    }

    public static function fromDatabaseRow(array $row): self {
        return new self(
            id: (int)$row['id'],
            transactionUuid: $row['transaction_uuid'],
            storeId: $row['store_id'],
            memberUuid: $row['member_uuid'],
            customerUuid: $row['customer_uuid'],
            transactionType: $row['transaction_type'],
            totalAmount: (float)$row['total_amount'],
            commissionAmount: (float)$row['commission_amount'],
            itemsSold: json_decode($row['items_sold'] ?? '[]', true),
            paymentMethod: $row['payment_method'],
            shiftId: $row['shift_id'] ? (int)$row['shift_id'] : null,
            notes: $row['notes'] ?? '',
            createdAt: strtotime($row['created_at'])
        );
    }

    public static function generateUuid(): string {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}