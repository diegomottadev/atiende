<?php
use PHPUnit\Framework\TestCase;

class WebhookIdempotencyTest extends TestCase
{
    private \PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = getPlatformPDO();
        $this->pdo->exec("DELETE FROM webhook_events WHERE provider_event_id LIKE 'test_%'");
    }

    public function test_first_insert_returns_one_affected_row(): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT IGNORE INTO webhook_events (provider, provider_event_id, evento) VALUES (?,?,?)'
        );
        $stmt->execute(['stripe', 'test_evt_001', 'invoice.payment_succeeded']);
        $this->assertEquals(1, $stmt->rowCount());
    }

    public function test_duplicate_insert_returns_zero_affected_rows(): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT IGNORE INTO webhook_events (provider, provider_event_id, evento) VALUES (?,?,?)'
        );
        $stmt->execute(['stripe', 'test_evt_002', 'invoice.payment_succeeded']);
        // Second attempt — same event
        $stmt->execute(['stripe', 'test_evt_002', 'invoice.payment_succeeded']);
        $this->assertEquals(0, $stmt->rowCount());
    }
}
