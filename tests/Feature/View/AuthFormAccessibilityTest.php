<?php

namespace Tests\Feature\View;

use Dom\Element;
use Dom\HTMLDocument;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * Accessibility of the login/register forms (page and modal copies) and the password reset form, read
 * off the rendered DOM: every control is labelled, dialogs are named, password inputs carry a reveal
 * toggle, help text is visible and linked, and links out of the form do not destroy it.
 */
#[Group('Auth')]
final class AuthFormAccessibilityTest extends PublicTestCase
{
    #[Test]
    #[DataProvider('authModalProvider')]
    public function authModal_givenGuestOnHomePage_isNamedByItsFormHeading(
        string $modalId,
        string $headingId,
        string $headingTranslationKey,
    ): void {
        // Arrange & Act
        $document = $this->renderDocument('home');

        // Assert
        $modal = $document->getElementById($modalId);
        $this->assertNotNull($modal, sprintf('Expected a #%s modal', $modalId));
        $this->assertSame($headingId, $modal->getAttribute('aria-labelledby'));

        $heading = $document->getElementById($headingId);
        $this->assertNotNull($heading, sprintf('aria-labelledby points at #%s, which does not exist', $headingId));
        $this->assertTrue($modal->contains($heading), 'The heading naming the dialog must live inside it');
        $this->assertSame(__($headingTranslationKey), trim($heading->textContent));
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function authModalProvider(): array
    {
        return [
            'login'    => ['login_modal', 'modal-login_heading', 'view_common.forms.login.login'],
            'register' => ['register_modal', 'modal-register_heading', 'view_common.forms.register.register'],
        ];
    }

    #[Test]
    public function modal_givenNoLabelledBy_rendersNoEmptyAriaLabelledby(): void
    {
        // Arrange & Act - the home page renders several other modals that name no heading
        $document = $this->renderDocument('home');

        // Assert
        $this->assertNull($document->querySelector('[aria-labelledby=""]'));
    }

    /**
     * @param array<string, string> $parameters
     */
    #[Test]
    #[DataProvider('authFormProvider')]
    public function authForm_givenAuthPage_labelsEveryControl(string $routeName, array $parameters, string $formId): void
    {
        // Arrange & Act
        $document = $this->renderDocument($routeName, $parameters);

        // Assert - the region selects included: an unlabelled select was one of the original findings
        $controls = $this->authFormScope($document, $formId)->querySelectorAll('input:not([type="hidden"]), select, textarea');
        $this->assertGreaterThan(0, $controls->length);

        foreach ($controls as $control) {
            $id = $control->getAttribute('id');
            $this->assertNotEmpty($id, sprintf('A <%s name="%s"> has no id to label', $control->localName, $control->getAttribute('name')));
            $this->assertNotNull(
                $document->querySelector(sprintf('label[for="%s"]', $id)),
                sprintf('No <label for="%s">', $id),
            );
        }
    }

    /**
     * @param array<string, string> $parameters
     */
    #[Test]
    #[DataProvider('authFormProvider')]
    public function passwordInput_givenAuthForm_hasARevealToggle(string $routeName, array $parameters, string $formId): void
    {
        // Arrange & Act
        $document = $this->renderDocument($routeName, $parameters);

        // Assert
        $passwordInputs = $this->authFormScope($document, $formId)->querySelectorAll('input[type="password"]');
        $this->assertGreaterThan(0, $passwordInputs->length);

        foreach ($passwordInputs as $input) {
            $id = $input->getAttribute('id');
            $this->assertTrue($input->parentElement?->classList->contains('input-group'), sprintf('#%s is not in an .input-group', $id));

            $toggle = $input->nextElementSibling;
            $this->assertNotNull($toggle, sprintf('#%s has no reveal toggle', $id));
            $this->assertSame('button', $toggle->localName);
            $this->assertSame('button', $toggle->getAttribute('type'), 'The toggle must never submit the form');
            $this->assertSame($id, $toggle->getAttribute('aria-controls'));
            $this->assertSame('false', $toggle->getAttribute('aria-pressed'));
            $this->assertSame(__('view_common.forms.passwordinput.show_password'), $toggle->getAttribute('aria-label'));
            $this->assertSame('true', $toggle->querySelector('i')?->getAttribute('aria-hidden'));
            $this->assertTrue($input->hasAttribute('required'));
        }
    }

    /**
     * @return array<string, array{string, array<string, string>, string}>
     */
    public static function authFormProvider(): array
    {
        return [
            'login page'     => ['login', [], 'login_form'],
            'login modal'    => ['home', [], 'modal-login_form'],
            'register page'  => ['register', [], 'register_form'],
            'register modal' => ['home', [], 'modal-register_form'],
            'password reset' => ['password.reset', ['token' => 'a-reset-token'], 'reset_password_form'],
        ];
    }

    #[Test]
    #[DataProvider('credentialFormProvider')]
    public function authForm_givenCredentialForm_marksEveryRequiredControlWithAHiddenAsterisk(string $routeName, string $formId): void
    {
        // Arrange & Act
        $document = $this->renderDocument($routeName);
        $scope    = $this->authFormScope($document, $formId);

        // Assert - `required` already exposes the state to assistive technology, so the visual
        // asterisk must stay out of the accessible name
        $asterisks = $scope->querySelectorAll('.form-required');
        $this->assertGreaterThan(0, $asterisks->length);
        foreach ($asterisks as $asterisk) {
            $this->assertSame('true', $asterisk->getAttribute('aria-hidden'));
        }

        foreach ($document->getElementById($formId)?->querySelectorAll('[required]') ?? [] as $control) {
            $label = $document->querySelector(sprintf('label[for="%s"]', $control->getAttribute('id')));
            $this->assertNotNull($label?->querySelector('.form-required'), sprintf('#%s is required but its label is unmarked', $control->getAttribute('id')));
        }
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function credentialFormProvider(): array
    {
        return [
            'login page'     => ['login', 'login_form'],
            'login modal'    => ['home', 'modal-login_form'],
            'register page'  => ['register', 'register_form'],
            'register modal' => ['home', 'modal-register_form'],
        ];
    }

    #[Test]
    #[DataProvider('registerFormProvider')]
    public function registerForm_givenAuthPage_opensEveryLegalLinkInANewTab(string $routeName, string $formId): void
    {
        // Arrange & Act
        $document = $this->renderDocument($routeName);
        $scope    = $this->authFormScope($document, $formId);

        // Assert - both the consent checkbox label and the OAuth column link to all three documents
        foreach (['legal.terms', 'legal.privacy', 'legal.cookies'] as $legalRoute) {
            $links = $scope->querySelectorAll(sprintf('a[href="%s"]', route($legalRoute)));
            $this->assertSame(2, $links->length, sprintf('Expected two links to %s', $legalRoute));

            foreach ($links as $link) {
                $this->assertSame('_blank', $link->getAttribute('target'));
                $this->assertContains('noopener', explode(' ', (string)$link->getAttribute('rel')));

                $hint = $document->getElementById((string)$link->getAttribute('aria-describedby'));
                $this->assertNotNull($hint, 'A new-tab link must describe that it opens a new tab');
                $this->assertSame(__('view_common.forms.register.opens_in_new_tab'), trim($hint->textContent));
            }
        }

        // The hint is a description, not link text, so the checkbox label does not repeat it
        $legalLabel = $document->querySelector(sprintf('label[for="%s"]', $this->legalAgreedId($formId)));
        $this->assertStringNotContainsString(__('view_common.forms.register.opens_in_new_tab'), (string)$legalLabel?->textContent);
    }

    #[Test]
    #[DataProvider('registerFormProvider')]
    public function registerForm_givenAuthPage_describesUsernameAndEmailWithVisibleHelpText(string $routeName, string $formId): void
    {
        // Arrange & Act
        $document = $this->renderDocument($routeName);
        $form     = $document->getElementById($formId);
        $this->assertNotNull($form);

        // Assert - help that only lived in a hover tooltip was unreachable by keyboard and touch
        $this->assertNull($form->querySelector('[data-bs-toggle="tooltip"]'));
        $this->assertNull($form->querySelector('.fa-info-circle'));

        foreach (['name' => 'view_common.forms.register.username_title', 'email' => 'view_common.forms.register.email_address_title'] as $name => $translationKey) {
            $input = $form->querySelector(sprintf('input[name="%s"]', $name));
            $help  = $document->getElementById((string)$input?->getAttribute('aria-describedby'));
            $this->assertNotNull($help, sprintf('The %s input is not described by any help text', $name));
            $this->assertTrue($help->classList->contains('form-text'));
            $this->assertSame(__($translationKey), trim($help->textContent));
        }
    }

    #[Test]
    #[DataProvider('registerFormProvider')]
    public function registerForm_givenAuthPage_requiresTheLegalAgreementAndPutsTheCheckboxFirst(string $routeName, string $formId): void
    {
        // Arrange & Act
        $document = $this->renderDocument($routeName);

        // Assert - the checkbox precedes its label, so it comes before the label's links in tab order
        $checkbox = $document->getElementById($this->legalAgreedId($formId));
        $this->assertNotNull($checkbox);
        $this->assertTrue($checkbox->hasAttribute('required'));
        $this->assertSame('label', $checkbox->nextElementSibling?->localName);
        $this->assertSame($checkbox->getAttribute('id'), $checkbox->nextElementSibling->getAttribute('for'));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function registerFormProvider(): array
    {
        return [
            'register page'  => ['register', 'register_form'],
            'register modal' => ['home', 'modal-register_form'],
        ];
    }

    /**
     * @param array<string, string> $parameters
     */
    #[Test]
    #[DataProvider('autocompleteProvider')]
    public function authForm_givenAuthPage_setsAutocompleteForPasswordManagers(
        string $routeName,
        array  $parameters,
        string $inputId,
        string $expectedAutocomplete,
    ): void {
        // Arrange & Act
        $document = $this->renderDocument($routeName, $parameters);

        // Assert
        $this->assertSame($expectedAutocomplete, $document->getElementById($inputId)?->getAttribute('autocomplete'));
    }

    /**
     * @return array<string, array{string, array<string, string>, string, string}>
     */
    public static function autocompleteProvider(): array
    {
        return [
            'login email'               => ['login', [], 'login_email', 'username email'],
            'login password'            => ['login', [], 'login_password', 'current-password'],
            'register username'         => ['register', [], 'register_name', 'username'],
            'register email'            => ['register', [], 'register_email', 'email'],
            'register password'         => ['register', [], 'register_password', 'new-password'],
            'register confirm password' => ['register', [], 'register_password-confirm', 'new-password'],
            'reset password'            => ['password.reset', ['token' => 'a-reset-token'], 'password', 'new-password'],
            'reset confirm password'    => ['password.reset', ['token' => 'a-reset-token'], 'password-confirm', 'new-password'],
        ];
    }

    #[Test]
    public function oauthButtons_givenLoginPage_nameEveryProviderInTextAndHideTheIcons(): void
    {
        // Arrange & Act
        $document = $this->renderDocument('login');

        // Assert
        $buttons = $this->authFormScope($document, 'login_form')->querySelectorAll('.btn-oauth');
        $this->assertSame(3, $buttons->length);

        foreach ($buttons as $button) {
            $this->assertNotSame('', trim($button->textContent), 'An OAuth button needs visible text as its name');
            foreach ($button->querySelectorAll('i') as $icon) {
                $this->assertSame('true', $icon->getAttribute('aria-hidden'));
            }
        }
    }

    #[Test]
    public function passwordInput_givenFailedRegistration_marksTheInputInvalidAndRendersTheErrorBelowTheGroup(): void
    {
        // Arrange - a short, mismatched password fails validation without creating a user
        $this->from(route('register'))->post(route('register'), [
            'name'                  => 'accessibility-test',
            'email'                 => 'not-an-email',
            'password'              => 'short',
            'password_confirmation' => 'different',
        ]);

        // Act
        $document = $this->renderDocument('register');

        // Assert - the message sits below the whole input group, not between the input and its toggle
        $input = $document->getElementById('register_password');
        $this->assertNotNull($input);
        $this->assertTrue($input->classList->contains('is-invalid'));
        $this->assertSame('true', $input->getAttribute('aria-invalid'));

        $feedback = $input->parentElement?->nextElementSibling;
        $this->assertNotNull($feedback);
        $this->assertTrue($feedback->classList->contains('invalid-feedback'));
        $this->assertTrue($feedback->classList->contains('d-block'));
        $this->assertSame('alert', $feedback->getAttribute('role'));
    }

    /**
     * @param array<string, string> $parameters
     */
    private function renderDocument(string $routeName, array $parameters = []): HTMLDocument
    {
        $content = (string)$this->get(route($routeName, $parameters))->assertOk()->getContent();

        return HTMLDocument::createFromString($content, LIBXML_NOERROR);
    }

    /**
     * The .auth-form container of a form: the row holding both the credential and the OAuth column
     * for login/register, the form itself for the password reset form.
     */
    private function authFormScope(HTMLDocument $document, string $formId): Element
    {
        $form = $document->getElementById($formId);
        $this->assertNotNull($form, sprintf('Expected a #%s form', $formId));

        $scope = $form->closest('.auth-form');
        $this->assertNotNull($scope, sprintf('#%s is not inside an .auth-form', $formId));

        return $scope;
    }

    private function legalAgreedId(string $formId): string
    {
        return str_starts_with($formId, 'modal-') ? 'modal-legal_agreed' : 'legal_agreed';
    }
}
