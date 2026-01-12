<?php

namespace Integration;

use Models\User\User;
use PHPUnit\Framework\TestCase;

class UserPaginationIntegrationTest extends TestCase
{
    private $testUserIds = [];

    protected function tearDown(): void
    {
        foreach ($this->testUserIds as $id) {
            if ($id !== null) {
                User::deleteAccount($id);
            }
        }
        parent::tearDown();
    }

    public function testGetUsersPaginated(): void
    {
        $user = new User();

        // Créer 5 utilisateurs
        for ($i = 1; $i <= 5; $i++) {
            $token = bin2hex(random_bytes(32));
            $user->register("User$i", "Test", "userpag$i@test. com", 'Pass123', 'etudiant', $token);
            $userData = $user->findByEmail("userpag$i@test.com");

            if ($userData) {
                $this->testUserIds[] = $userData['id'];
            }
        }

        // Tester la pagination :  2 résultats par page
        $page1 = $user->getUsersPaginated(2, 0);
        $this->assertGreaterThanOrEqual(2, count($page1));

        $page2 = $user->getUsersPaginated(2, 2);
        $this->assertGreaterThanOrEqual(2, count($page2));
    }

    public function testGetUsersPaginatedWithSorting(): void
    {
        $user = new User();

        // Créer 3 utilisateurs avec des NOMS différents (nom = 2e paramètre)
        $token1 = bin2hex(random_bytes(32));
        $user->register('Test', 'Zorro', 'zorro@testpag.com', 'Pass123', 'etudiant', $token1);
        $userData1 = $user->findByEmail('zorro@testpag.com');
        if ($userData1) $this->testUserIds[] = $userData1['id'];

        $token2 = bin2hex(random_bytes(32));
        $user->register('Test', 'Alpha', 'alpha@testpag.com', 'Pass123', 'etudiant', $token2);
        $userData2 = $user->findByEmail('alpha@testpag.com');
        if ($userData2) $this->testUserIds[] = $userData2['id'];

        $token3 = bin2hex(random_bytes(32));
        $user->register('Test', 'Beta', 'beta@testpag.com', 'Pass123', 'etudiant', $token3);
        $userData3 = $user->findByEmail('beta@testpag.com');
        if ($userData3) $this->testUserIds[] = $userData3['id'];

        // Vérifier que les 3 utilisateurs ont bien été créés
        $this->assertCount(3, $this->testUserIds);

        // Vérifier que getUsersPaginated fonctionne avec tri ASC
        $usersAsc = $user->getUsersPaginated(10, 0, 'nom', 'ASC');
        $this->assertIsArray($usersAsc);
        $this->assertGreaterThan(0, count($usersAsc));

        // Vérifier que getUsersPaginated fonctionne avec tri DESC
        $usersDesc = $user->getUsersPaginated(10, 0, 'nom', 'DESC');
        $this->assertIsArray($usersDesc);
        $this->assertGreaterThan(0, count($usersDesc));

        // Vérifier que nos utilisateurs existent bien en BDD avec le BON NOM
        $alphaData = $user->findByEmail('alpha@testpag.com');
        $this->assertNotNull($alphaData);
        $this->assertEquals('Alpha', $alphaData['nom']);  // ← nom = 2e param = 'Alpha'

        $betaData = $user->findByEmail('beta@testpag.com');
        $this->assertNotNull($betaData);
        $this->assertEquals('Beta', $betaData['nom']);

        $zorroData = $user->findByEmail('zorro@testpag.com');
        $this->assertNotNull($zorroData);
        $this->assertEquals('Zorro', $zorroData['nom']);
    }

    public function testCountUsers(): void
    {
        $user = new User();

        $countBefore = $user->countUsers();

        // Ajouter 3 utilisateurs
        for ($i = 1; $i <= 3; $i++) {
            $token = bin2hex(random_bytes(32));
            $user->register("Count$i", "Test", "count$i@testpag.com", 'Pass123', 'etudiant', $token);
            $userData = $user->findByEmail("count$i@testpag. com");
            if ($userData) {
                $this->testUserIds[] = $userData['id'];
            }
        }

        $countAfter = $user->countUsers();

        // Vérifier qu'on a au moins 3 utilisateurs de plus
        $this->assertGreaterThanOrEqual($countBefore + 3, $countAfter);
    }
}