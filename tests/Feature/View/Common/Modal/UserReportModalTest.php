<?php

namespace Tests\Feature\View\Common\Modal;

use App\Models\Laratrust\Role;
use App\Models\User;
use DOMDocument;
use DOMElement;
use DOMXPath;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('View')]
#[Group('UserReport')]
final class UserReportModalTest extends PublicTestCase
{
    #[Test]
    public function dungeonrouteReport_givenGuest_returnsLoginPromptInsteadOfForm(): void
    {
        // Arrange
        $this->actingAsGuest();

        // Act
        $xpath = $this->render('common.modal.userreport.dungeonroute');

        // Assert
        $loginLinks = $xpath->query('//a[@id="userreport_dungeonroute_login_link"]');
        $this->assertSame(1, $loginLinks->length);
        $loginLink = $loginLinks->item(0);
        $this->assertInstanceOf(DOMElement::class, $loginLink);
        $this->assertSame(route('login'), $loginLink->getAttribute('href'));
        $this->assertSame(0, $xpath->query('//textarea[@name="dungeonroute_report_message"]')->length);
        $this->assertSame(0, $xpath->query('//input[@name="dungeonroute_report_username"]')->length);
    }

    #[Test]
    public function dungeonrouteReport_givenUser_returnsFormWithoutNameField(): void
    {
        // Arrange
        $user = null;

        try {
            $user = $this->createUserWithUserRole();
            $this->actingAs($user);

            // Act
            $xpath = $this->render('common.modal.userreport.dungeonroute');

            // Assert
            $this->assertSame(1, $xpath->query('//textarea[@name="dungeonroute_report_message"]')->length);
            $this->assertSame(0, $xpath->query('//input[@name="dungeonroute_report_username"]')->length);
            $this->assertSame(0, $xpath->query('//a[@id="userreport_dungeonroute_login_link"]')->length);
        } finally {
            $user?->delete();
        }
    }

    #[Test]
    public function enemyDetails_givenGuest_returnsNoReportForm(): void
    {
        // Arrange
        $this->actingAsGuest();

        // Act
        $xpath = $this->render('common.modal.enemydetails');

        // Assert
        $this->assertSame(1, $xpath->query('//div[@id="enemy_details_modal_body"]')->length);
        $this->assertSame(0, $xpath->query('//button[@data-bs-target="#enemy_report_collapse"]')->length);
        $this->assertSame(0, $xpath->query('//div[@id="enemy_report_collapse"]')->length);
        $this->assertSame(0, $xpath->query('//input[@name="enemy_report_username"]')->length);
    }

    #[Test]
    public function enemyDetails_givenUser_returnsReportFormWithoutNameField(): void
    {
        // Arrange
        $user = null;

        try {
            $user = $this->createUserWithUserRole();
            $this->actingAs($user);

            // Act
            $xpath = $this->render('common.modal.enemydetails');

            // Assert
            $this->assertSame(1, $xpath->query('//button[@data-bs-target="#enemy_report_collapse"]')->length);
            $this->assertSame(1, $xpath->query('//textarea[@name="enemy_report_message"]')->length);
            $this->assertSame(0, $xpath->query('//input[@name="enemy_report_username"]')->length);
        } finally {
            $user?->delete();
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function render(string $view, array $data = []): DOMXPath
    {
        $html = view($view, $data)->render();

        $document = new DOMDocument();
        @$document->loadHTML(sprintf('<?xml encoding="UTF-8"><body>%s</body>', $html));

        return new DOMXPath($document);
    }

    private function createUserWithUserRole(): User
    {
        $user = User::factory()->create();
        $user->addRole(Role::ROLE_USER);

        return $user;
    }
}
