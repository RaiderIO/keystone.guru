<?php

namespace App\Service\Notification;

use App\Models\Notification\Enums\NotificationType;
use App\Models\Notification\NotificationMessage;
use App\Service\Notification\Dtos\RenderedNotification;
use App\Service\Notification\Renderer\NotificationRendererInterface;
use RuntimeException;

class NotificationRenderService implements NotificationRenderServiceInterface
{
    /**
     * @param array<int, NotificationRendererInterface> $renderers
     */
    public function __construct(
        private readonly array $renderers,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function render(NotificationMessage $notificationMessage, string $locale, bool $read): RenderedNotification
    {
        $content = $this->rendererFor($notificationMessage->type)->render($notificationMessage, $locale);

        return new RenderedNotification(
            $notificationMessage->id,
            $notificationMessage->type,
            $content->title,
            $content->body,
            $notificationMessage->url,
            $notificationMessage->type->icon(),
            $notificationMessage->created_at,
            $read,
        );
    }

    /**
     * {@inheritDoc}
     */
    public function hasRendererFor(NotificationType $type): bool
    {
        foreach ($this->renderers as $renderer) {
            if ($renderer->supports($type)) {
                return true;
            }
        }

        return false;
    }

    private function rendererFor(NotificationType $type): NotificationRendererInterface
    {
        foreach ($this->renderers as $renderer) {
            if ($renderer->supports($type)) {
                return $renderer;
            }
        }

        throw new RuntimeException(sprintf('No notification renderer supports type %s', $type->value));
    }
}
