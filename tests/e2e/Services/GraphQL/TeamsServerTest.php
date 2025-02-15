<?php

namespace Tests\E2E\Services\GraphQL;

use Tests\E2E\Client;
use Tests\E2E\Scopes\ProjectCustom;
use Tests\E2E\Scopes\Scope;
use Tests\E2E\Scopes\SideServer;
use Utopia\Database\ID;

class TeamsServerTest extends Scope
{
    use ProjectCustom;
    use Base;
    use SideServer;

    public function testCreateTeam(): array
    {
        $projectId = $this->getProject()['$id'];
        $query = $this->getQuery(self::$CREATE_TEAM);
        $graphQLPayload = [
            'query' => $query,
            'variables' => [
                'teamId' => ID::unique(),
                'name' => 'Team Name',
                'roles' => ['admin', 'developer', 'guest'],
            ],
        ];

        $team = $this->client->call(Client::METHOD_POST, '/graphql', \array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $projectId,
        ], $this->getHeaders()), $graphQLPayload);

        $this->assertIsArray($team['body']['data']);
        $this->assertArrayNotHasKey('errors', $team['body']);
        $team = $team['body']['data']['teamsCreate'];
        $this->assertEquals('Team Name', $team['name']);

        return $team;
    }

    /**
     * @depends testCreateTeam
     */
    public function testCreateTeamMembership($team): array
    {
        $projectId = $this->getProject()['$id'];
        $query = $this->getQuery(self::$CREATE_TEAM_MEMBERSHIP);
        $graphQLPayload = [
            'query' => $query,
            'variables' => [
                'teamId' => $team['_id'],
                'email' => 'user@appwrite.io',
                'roles' => ['developer'],
                'url' => 'http://localhost/membership',
            ],
        ];

        $membership = $this->client->call(Client::METHOD_POST, '/graphql', \array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $projectId,
        ], $this->getHeaders()), $graphQLPayload);

        $this->assertIsArray($membership['body']['data']);
        $this->assertArrayNotHasKey('errors', $membership['body']);
        $membership = $membership['body']['data']['teamsCreateMembership'];
        $this->assertEquals($team['_id'], $membership['teamId']);
        $this->assertEquals(['developer'], $membership['roles']);

        return $membership;
    }

    public function testGetTeams()
    {
        $projectId = $this->getProject()['$id'];
        $query = $this->getQuery(self::$GET_TEAMS);
        $graphQLPayload = [
            'query' => $query,
        ];

        $teams = $this->client->call(Client::METHOD_POST, '/graphql', \array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $projectId,
        ], $this->getHeaders()), $graphQLPayload);

        $this->assertIsArray($teams['body']['data']);
        $this->assertArrayNotHasKey('errors', $teams['body']);
    }

    /**
     * @depends testCreateTeam
     */
    public function testGetTeam($team)
    {
        $projectId = $this->getProject()['$id'];
        $query = $this->getQuery(self::$GET_TEAM);
        $graphQLPayload = [
            'query' => $query,
            'variables' => [
                'teamId' => $team['_id'],
            ],
        ];

        $team = $this->client->call(Client::METHOD_POST, '/graphql', \array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $projectId,
        ], $this->getHeaders()), $graphQLPayload);

        $this->assertIsArray($team['body']['data']);
        $this->assertArrayNotHasKey('errors', $team['body']);
        $team = $team['body']['data']['teamsGet'];
        $this->assertIsArray($team);
    }

    /**
     * @depends testCreateTeam
     */
    public function testGetTeamMemberships($team)
    {
        $projectId = $this->getProject()['$id'];
        $query = $this->getQuery(self::$GET_TEAM_MEMBERSHIPS);
        $graphQLPayload = [
            'query' => $query,
            'variables' => [
                'teamId' => $team['_id'],
            ],
        ];

        $memberships = $this->client->call(Client::METHOD_POST, '/graphql', \array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $projectId,
        ], $this->getHeaders()), $graphQLPayload);

        $this->assertIsArray($memberships['body']['data']);
        $this->assertArrayNotHasKey('errors', $memberships['body']);
        $this->assertIsArray($memberships['body']['data']['teamsListMemberships']);
    }

    /**
     * @depends testCreateTeam
     * @depends testCreateTeamMembership
     */
    public function testGetTeamMembership($team, $membership)
    {
        $projectId = $this->getProject()['$id'];
        $query = $this->getQuery(self::$GET_TEAM_MEMBERSHIP);
        $graphQLPayload = [
            'query' => $query,
            'variables' => [
                'teamId' => $team['_id'],
                'membershipId' => $membership['_id'],
            ],
        ];

        $membership = $this->client->call(Client::METHOD_POST, '/graphql', \array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $projectId,
        ], $this->getHeaders()), $graphQLPayload);

        $this->assertIsArray($membership['body']['data']['teamsGetMembership']);
        $this->assertArrayNotHasKey('errors', $membership['body']);
    }

    /**
     * @depends testCreateTeam
     */
    public function testUpdateTeam($team)
    {
        $projectId = $this->getProject()['$id'];
        $query = $this->getQuery(self::$UPDATE_TEAM);
        $graphQLPayload = [
            'query' => $query,
            'variables' => [
                'teamId' => $team['_id'],
                'name' => 'New Name',
            ],
        ];

        $team = $this->client->call(Client::METHOD_POST, '/graphql', \array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $projectId,
        ], $this->getHeaders()), $graphQLPayload);

        $this->assertIsArray($team['body']['data']);
        $this->assertArrayNotHasKey('errors', $team['body']);
        $team = $team['body']['data']['teamsUpdate'];
        $this->assertEquals('New Name', $team['name']);
    }

    /**
     * @depends testCreateTeam
     * @depends testCreateTeamMembership
     */
    public function testUpdateTeamMembershipRoles($team, $membership)
    {
        $projectId = $this->getProject()['$id'];
        $query = $this->getQuery(self::$UPDATE_TEAM_MEMBERSHIP_ROLES);
        $graphQLPayload = [
            'query' => $query,
            'variables' => [
                'teamId' => $team['_id'],
                'membershipId' => $membership['_id'],
                'roles' => ['developer', 'admin'],
            ],
        ];

        $membership = $this->client->call(Client::METHOD_POST, '/graphql', \array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $projectId,
        ], $this->getHeaders()), $graphQLPayload);

        $this->assertIsArray($membership['body']['data']);
        $this->assertArrayNotHasKey('errors', $membership['body']);
        $membership = $membership['body']['data']['teamsUpdateMembershipRoles'];
        $this->assertEquals(['developer', 'admin'], $membership['roles']);
    }

    /**
     * @depends testCreateTeam
     * @depends testCreateTeamMembership
     */
    public function testDeleteTeamMembership($team, $membership)
    {
        $projectId = $this->getProject()['$id'];
        $query = $this->getQuery(self::$DELETE_TEAM_MEMBERSHIP);
        $graphQLPayload = [
            'query' => $query,
            'variables' => [
                'teamId' => $team['_id'],
                'membershipId' => $membership['_id'],
            ],
        ];

        $team = $this->client->call(Client::METHOD_POST, '/graphql', \array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $projectId,
        ], $this->getHeaders()), $graphQLPayload);

        $this->assertIsNotArray($team['body']);
        $this->assertEquals(204, $team['headers']['status-code']);
    }

    public function testDeleteTeam()
    {
        $team = $this->testCreateTeam();

        $projectId = $this->getProject()['$id'];
        $query = $this->getQuery(self::$DELETE_TEAM);
        $graphQLPayload = [
            'query' => $query,
            'variables' => [
                'teamId' => $team['_id'],
            ],
        ];

        $team = $this->client->call(Client::METHOD_POST, '/graphql', \array_merge([
            'content-type' => 'application/json',
            'x-appwrite-project' => $projectId,
        ], $this->getHeaders()), $graphQLPayload);

        $this->assertIsNotArray($team['body']);
        $this->assertEquals(204, $team['headers']['status-code']);
    }
}
