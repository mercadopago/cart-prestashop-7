<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 *
 * @author    PrestaShop SA and Contributors
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

use MercadoPago\PP\Sdk\Common\AbstractEntity;
use MercadoPago\PP\Sdk\Common\Manager;
use MercadoPago\PP\Sdk\HttpClient\Response;

require_once __DIR__ . '/../../PaymentIssuanceRejected.php';

/** Preserve the pinned SDK's serialization/transport while retaining definite rejection evidence. */
final class PaymentIssuanceManager extends Manager
{
    private $delegate;

    public function __construct(Manager $delegate)
    {
        $this->delegate = $delegate;
    }

    public function getHeader(array $customHeaders = []): array
    {
        return $this->delegate->getHeader($customHeaders);
    }

    public function getEntityUri(AbstractEntity $entity, string $method, array $params = [], array $queryStrings = [])
    {
        return $this->delegate->getEntityUri($entity, $method, $params, $queryStrings);
    }

    public function execute(AbstractEntity $entity, string $uri, string $method = 'get', array $headers = [])
    {
        return $this->delegate->execute($entity, $uri, $method, $headers);
    }

    public function handleResponse(Response $response, string $method, ?AbstractEntity $entity = null)
    {
        $status = $response->getStatus();
        // CurlRequester decodes JSON as arrays; the SDK's getData PHPDoc incorrectly says object|null.
        /** @var mixed $data */
        $data = $response->getData();
        if ($method === 'post' && in_array($status, [400, 422], true) && is_array($data)
            && !array_intersect(['id', 'payment_id', 'preference_id'], array_keys($data))
            && in_array($data['error'] ?? null, ['bad_request', 'validation_error'], true)
            && (!array_key_exists('status', $data) || $data['status'] === $status)
            && isset($data['message']) && is_string($data['message']) && trim($data['message']) !== ''
        ) {
            return new PaymentIssuanceRejected($data['message']);
        }

        return $this->delegate->handleResponse($response, $method, $entity);
    }
}
