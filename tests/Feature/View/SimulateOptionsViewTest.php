<?php

namespace Tests\Feature\View;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('View')]
#[Group('SimulationCraft')]
final class SimulateOptionsViewTest extends PublicTestCase
{
    #[Test]
    public function simulateOptions_givenTheConfiguredAffixScaling_statesItInTheAffixesTooltip(): void
    {
        // Arrange
        config()->set('keystoneguru.keystone.affix_scaling_factor.fortified', 1.15);
        config()->set('keystoneguru.keystone.affix_scaling_factor.tyrannical', 1.4);

        // Act
        $view = $this->view('common.modal.simulateoptions.default', ['season' => null]);

        // Assert
        $view->assertSee("non-boss enemies' health by 15%, Tyrannical to scale boss' health by 40%.");
    }
}
