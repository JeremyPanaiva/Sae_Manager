<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Models\User\User;
use Models\Database;

class UserPaginationIntegrationTest extends TestCase
{
    /** @var list<int> */
    private array $testUserIds = [];

    protected function tearDown(): void
    {
        foreach ($this->testUserIds as $id) {
            User::deleteAccount($id);
        }
        parent::tearDown();
    }

    public function testGetUsersPaginated(): void
    {
        $user = new User();

        // Créer 5 utilisateurs
        for ($i = 1; $i <= 5; $i++) {
            $token = bin2hex(random_bytes(32));
            $user->register("User$i", "Test", "userpag$i@test.com", 'Pass123', 'etudiant', $token);
            $userData = $user->findByEmail("userpag$i@test. com");

            if ($userData) {
                $this->assertArrayHasKey('id', $userData);
                $userId = $userData['id'];
                $this->assertIsInt($userId);
                $this->testUserIds[] = $userId;
            }
        }

        // Tester la pagination :  2 r��sultats par page
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
        if ($userData1) {
            $this->assertArrayHasKey('id', $userData1);
            $userId1 = $userData1['id'];
            $this->assertIsInt($userId1);
            $this->testUserIds[] = $userId1;
        }

        $token2 = bin2hex(random_bytes(32));
        $user->register('Test', 'Alpha', 'alpha@testpag.com', 'Pass123', 'etudiant', $token2);
        $userData2 = $user->findByEmail('alpha@testpag.com');
        if ($userData2) {
            $this->assertArrayHasKey('id', $userData2);
            $userId2 = $userData2['id'];
            $this->assertIsInt($userId2);
            $this->testUserIds[] = $userId2;
        }

        $token3 = bin2hex(random_bytes(32));
        $user->register('Test', 'Beta', 'beta@testpag.com', 'Pass123', 'etudiant', $token3);
        $userData3 = $user->findByEmail('beta@testpag.com');
        if ($userData3) {
            $this->assertArrayHasKey('id', $userData3);
            $userId3 = $userData3['id'];
            $this->assertIsInt($userId3);
            $this->testUserIds[] = $userId3;
        }

        // Vérifier que les 3 utilisateurs ont bien été créés
        $this->assertCount(3, $this->testUserIds);

        // Vérifier que getUsersPaginated fonctionne avec tri ASC
        $usersAsc = $user->getUsersPaginated(10, 0, 'nom', 'ASC');
        $this->assertGreaterThan(0, count($usersAsc));

        // Vérifier que getUsersPaginated fonctionne avec tri DESC
        $usersDesc = $user->getUsersPaginated(10, 0, 'nom', 'DESC');
        $this->assertGreaterThan(0, count($usersDesc));

        // Vérifier que nos utilisateurs existent bien en BDD avec le BON NOM
        $alphaData = $user->findByEmail('alpha@testpag.com');
        $this->assertNotNull($alphaData);
        $this->assertEquals('Alpha', $alphaData['nom']);

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

        $createdCount = 0;

        // Ajouter 3 utilisateurs
        for ($i = 1; $i <= 3; $i++) {
            $token = bin2hex(random_bytes(32));
            $email = "countuser" . time() . $i . "@testpag.com";

            $user->register("Count$i", "Test", $email, 'Pass123', 'etudiant', $token);
            $userData = $user->findByEmail($email);

            if ($userData) {
                $this->assertArrayHasKey('id', $userData);
                $userId = $userData['id'];
                $this->assertIsInt($userId);
                $this->testUserIds[] = $userId;
                $createdCount++;
            }
        }

        $countAfter = $user->countUsers();

        // Vérifier qu'on a bien créé 3 utilisateurs
        $this->assertEquals(3, $createdCount);

        // Vérifier le comptage en BDD
        $this->assertEquals($countBefore + $createdCount, $countAfter);
    }
}
