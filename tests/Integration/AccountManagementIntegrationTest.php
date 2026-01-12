<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Models\User\User;
use Models\Database;

class AccountManagementIntegrationTest extends TestCase
{
    private $testUserId;

    protected function tearDown(): void
    {
        if ($this->testUserId) {
            User::deleteAccount($this->testUserId);
        }
        parent::tearDown();
    }

    public function testGetUserById(): void
    {
        $user = new User();
        $token = bin2hex(random_bytes(32));
        $email = 'getbyid' .  uniqid() . '@test.com'; // ← UNIQID

        $user->register('Test', 'User', $email, 'Pass123', 'etudiant', $token);

        $userData = $user->findByEmail($email);
        $this->assertNotNull($userData, "L'utilisateur n'a pas été créé avec l'email $email");

        $this->testUserId = $userData['id'];

        $retrievedUser = User:: getById($this->testUserId);

        $this->assertNotNull($retrievedUser);
        $this->assertEquals('User', $retrievedUser['nom']);
        $this->assertEquals('Test', $retrievedUser['prenom']);
    }

    public function testUpdateProfile(): void
    {
        $user = new User();
        $token = bin2hex(random_bytes(32));
        $email = 'update' . uniqid() . '@test.com';

        $user->register('Old', 'Name', $email, 'Pass123', 'etudiant', $token);

        $userData = $user->findByEmail($email);
        $this->assertNotNull($userData, "L'utilisateur n'a pas été créé");

        $this->testUserId = $userData['id'];

        // Mettre à jour le profil
        $conn = Database::getConnection();
        $stmt = $conn->prepare("UPDATE users SET nom = ?, prenom = ?  WHERE id = ?");
        $nom = 'NewName';
        $prenom = 'NewFirst';
        $stmt->bind_param("ssi", $nom, $prenom, $this->testUserId);
        $stmt->execute();
        $stmt->close();

        $updated = User::getById($this->testUserId);
        $this->assertEquals('NewName', $updated['nom']);
        $this->assertEquals('NewFirst', $updated['prenom']);
    }

    public function testUpdateEmail(): void
    {
        $user = new User();
        $token = bin2hex(random_bytes(32));
        $oldEmail = 'oldemail' . uniqid() . '@test.com';
        $newEmail = 'newemail' . uniqid() . '@test.com';

        $user->register('Test', 'User', $oldEmail, 'Pass123', 'etudiant', $token);

        $userData = $user->findByEmail($oldEmail);
        $this->assertNotNull($userData, "L'utilisateur n'a pas été créé");

        $this->testUserId = $userData['id'];

        // Mettre à jour l'email
        $newToken = bin2hex(random_bytes(32));
        $user->updateEmail($this->testUserId, $newEmail, $newToken);

        // Ancien email n'existe plus
        $this->assertNull($user->findByEmail($oldEmail));

        // Nouvel email existe et n'est pas vérifié
        $newUser = $user->findByEmail($newEmail);
        $this->assertNotNull($newUser);
        $this->assertEquals(0, $newUser['is_verified']);
    }

    public function testChangePassword(): void
    {
        $user = new User();
        $token = bin2hex(random_bytes(32));
        $email = 'changepass' . uniqid() . '@test.com';

        $user->register('Test', 'User', $email, 'OldPass123', 'etudiant', $token);

        $userData = $user->findByEmail($email);
        $this->assertNotNull($userData, "L'utilisateur n'a pas été créé");

        $this->testUserId = $userData['id'];

        // Changer le mot de passe
        $conn = Database::getConnection();
        $newHashed = password_hash('NewPass456', PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET mdp = ?  WHERE id = ?");
        $stmt->bind_param("si", $newHashed, $this->testUserId);
        $stmt->execute();
        $stmt->close();

        $updated = $user->findByEmail($email);
        $this->assertNotNull($updated);

        // Nouveau mot de passe fonctionne
        $this->assertTrue(password_verify('NewPass456', $updated['mdp']));

        // Ancien mot de passe ne fonctionne plus
        $this->assertFalse(password_verify('OldPass123', $updated['mdp']));
    }

    public function testDeleteAccount(): void
    {
        $user = new User();
        $token = bin2hex(random_bytes(32));
        $email = 'delete' .  uniqid() . '@test.com';

        $user->register('Test', 'User', $email, 'Pass123', 'etudiant', $token);

        $userData = $user->findByEmail($email);
        $this->assertNotNull($userData, "L'utilisateur n'a pas été créé");

        $userId = $userData['id'];

        // Supprimer
        User:: deleteAccount($userId);

        // Vérifier que l'utilisateur n'existe plus
        $this->assertNull($user->findByEmail($email));
        $this->assertNull(User::getById($userId));

        $this->testUserId = null;
    }
}