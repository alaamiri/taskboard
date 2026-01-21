# Guide Complet Laravel - Projet Taskboard

## Table des matières

1. [Vue d'ensemble de l'architecture](#1-vue-densemble-de-larchitecture)
2. [Models](#2-models)
3. [Migrations](#3-migrations)
4. [Controllers](#4-controllers)
5. [Routes](#5-routes)
6. [Policies](#6-policies)
7. [Events](#7-events)
8. [Jobs & Queues](#8-jobs--queues)
9. [Broadcasting (Reverb)](#9-broadcasting-reverb)
10. [Frontend (Inertia + React)](#10-frontend-inertia--react)
11. [Tests](#11-tests)
12. [Factories & Seeders](#12-factories--seeders)
13. [API Resources](#13-api-resources)
14. [Services & Repositories](#14-services--repositories)
15. [Architecture projet critique/fédéral](#15-architecture-projet-critiquefédéral)
16. [Docker & Sail](#16-docker--sail)
17. [Commandes essentielles](#17-commandes-essentielles)
18. [Diagramme des relations](#18-diagramme-des-relations)

---

## 1. Vue d'ensemble de l'architecture

### Stack technique

| Technologie | Rôle |
|-------------|------|
| **Laravel 12** | Framework PHP backend |
| **PostgreSQL** | Base de données relationnelle |
| **Redis** | Cache, sessions, queues |
| **Laravel Sail** | Environnement Docker |
| **Laravel Horizon** | Monitoring des queues |
| **Laravel Reverb** | WebSockets temps réel |
| **Inertia.js** | Pont entre Laravel et React |
| **React** | Frontend SPA |
| **Tailwind CSS** | Framework CSS |

### Flux d'une requête HTTP
```
┌──────────────────────────────────────────────────────────────────────────────┐
│                           CYCLE DE VIE D'UNE REQUÊTE                         │
└──────────────────────────────────────────────────────────────────────────────┘

Utilisateur (Navigateur)
        │
        │  GET /boards/1
        ▼
┌─────────────────┐
│     ROUTES      │  routes/web.php
│                 │  Trouve: "BoardController@show"
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│   MIDDLEWARE    │  Vérifie: auth, CSRF, etc.
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│   CONTROLLER    │  BoardController@show($board)
│                 │  1. Autorise via Policy
│                 │  2. Récupère données via Model
│                 │  3. Retourne vue via Inertia
└────────┬────────┘
         │
    ┌────┴────┐
    │         │
    ▼         ▼
┌───────┐ ┌───────┐
│POLICY │ │ MODEL │
│       │ │       │
│Vérifie│ │Requête│
│permis.│ │  DB   │
└───────┘ └───┬───┘
              │
              ▼
       ┌────────────┐
       │ PostgreSQL │
       └──────┬─────┘
              │
              ▼
┌─────────────────┐
│    INERTIA      │  Passe les données à React
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  REACT (JSX)    │  Affiche la page
└────────┬────────┘
         │
         ▼
    Utilisateur voit la page
```

### Structure des dossiers
```
taskboard/
├── app/
│   ├── Actions/             # Actions métier (projet critique)
│   ├── DataTransferObjects/ # DTOs (projet critique)
│   ├── Events/              # Événements broadcast
│   ├── Http/
│   │   ├── Controllers/     # Controllers Web
│   │   │   └── Api/         # Controllers API
│   │   ├── Requests/        # Form Requests (validation)
│   │   └── Resources/       # API Resources (JSON)
│   ├── Jobs/                # Tâches en arrière-plan
│   ├── Models/              # Représentation des tables
│   ├── Policies/            # Autorisations
│   ├── Repositories/        # Accès aux données (projet critique)
│   └── Services/            # Logique métier
├── database/
│   ├── factories/           # Génération de données test
│   ├── migrations/          # Structure des tables
│   └── seeders/             # Données initiales
├── resources/
│   └── js/
│       ├── Components/      # Composants React réutilisables
│       ├── hooks/           # Hooks React personnalisés
│       ├── Layouts/         # Layouts de page
│       └── Pages/           # Pages Inertia
├── routes/
│   ├── web.php              # Routes web
│   ├── api.php              # Routes API
│   └── channels.php         # Autorisations WebSocket
└── tests/
    ├── Feature/             # Tests fonctionnels
    └── Unit/                # Tests unitaires
```

---

## 2. Models

### Définition

Un **Model** représente une table de la base de données. Il permet de :
- Définir les colonnes modifiables (`$fillable`)
- Définir les relations entre tables
- Manipuler les données (CRUD)

### Emplacement
```
app/Models/
├── User.php
├── Board.php
├── Column.php
└── Card.php
```

### Exemple complet : Board.php
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Board extends Model
{
    use HasFactory;  // Permet d'utiliser Board::factory()

    // Colonnes autorisées à être remplies
    protected $fillable = ['name', 'description', 'user_id'];

    // Relation : Board appartient à un User
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Relation : Board possède plusieurs Columns
    public function columns(): HasMany
    {
        return $this->hasMany(Column::class)->orderBy('position');
    }
}
```

### Types de relations

| Relation | Méthode | Description | Exemple |
|----------|---------|-------------|---------|
| **1 → N** | `hasMany()` | Un parent a plusieurs enfants | Board a plusieurs Columns |
| **N → 1** | `belongsTo()` | Un enfant appartient à un parent | Column appartient à Board |
| **1 → 1** | `hasOne()` | Un parent a un seul enfant | User a un Profile |

### Utilisation courante
```php
// CRÉER
$board = Board::create([
    'name' => 'Mon projet',
    'user_id' => auth()->id()
]);

// LIRE
$board = Board::find(1);                          // Par ID
$boards = Board::all();                           // Tous
$boards = Board::where('user_id', 1)->get();      // Avec condition

// LIRE AVEC RELATIONS (Eager Loading)
$board = Board::with('columns.cards')->find(1);   // Charge tout en 1 requête

// METTRE À JOUR
$board->update(['name' => 'Nouveau nom']);

// SUPPRIMER
$board->delete();

// VIA RELATIONS
$board->columns()->create(['name' => 'To Do', 'position' => 0]);
$columns = $board->columns;   // Collection de Column
$owner = $board->user;        // Objet User
```

### Commande de création
```bash
sail artisan make:model Board -mf
# -m = migration
# -f = factory
```

---

## 3. Migrations

### Définition

Une **Migration** définit la structure d'une table (colonnes, types, contraintes). Elle permet de :
- Versionner le schéma de la base de données
- Partager la structure avec l'équipe
- Recréer la base facilement

### Emplacement
```
database/migrations/
├── 2024_01_15_000001_create_boards_table.php
├── 2024_01_15_000002_create_columns_table.php
└── 2024_01_15_000003_create_cards_table.php
```

### Exemple complet : create_boards_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Exécuté lors de "migrate"
    public function up(): void
    {
        Schema::create('boards', function (Blueprint $table) {
            $table->id();                              // BIGINT AUTO_INCREMENT
            $table->foreignId('user_id')               // Clé étrangère
                  ->constrained()                      // Référence users.id
                  ->onDelete('cascade');               // Supprime les boards si user supprimé
            $table->string('name');                    // VARCHAR(255)
            $table->text('description')->nullable();   // TEXT, peut être NULL
            $table->timestamps();                      // created_at, updated_at
        });
    }

    // Exécuté lors de "migrate:rollback"
    public function down(): void
    {
        Schema::dropIfExists('boards');
    }
};
```

### Types de colonnes

| Méthode | Type SQL | Usage |
|---------|----------|-------|
| `$table->id()` | BIGINT AUTO_INCREMENT | Clé primaire |
| `$table->string('name')` | VARCHAR(255) | Texte court |
| `$table->text('content')` | TEXT | Texte long |
| `$table->integer('count')` | INTEGER | Nombre entier |
| `$table->boolean('active')` | BOOLEAN | Vrai/Faux |
| `$table->foreignId('user_id')` | BIGINT | Clé étrangère |
| `$table->timestamps()` | TIMESTAMP x2 | created_at, updated_at |

### Modificateurs

| Modificateur | Effet |
|--------------|-------|
| `->nullable()` | Peut être NULL |
| `->default('value')` | Valeur par défaut |
| `->unique()` | Valeur unique |
| `->after('column')` | Position après une colonne |

### Commandes
```bash
# Créer une migration
sail artisan make:migration create_boards_table
sail artisan make:migration add_status_to_boards_table

# Exécuter
sail artisan migrate

# Annuler la dernière
sail artisan migrate:rollback

# Tout recréer (ATTENTION: perd les données)
sail artisan migrate:fresh

# Voir le statut
sail artisan migrate:status
```

### Modifier une table existante
```php
// Pour AJOUTER une colonne
Schema::table('boards', function (Blueprint $table) {
    $table->string('status')->default('active')->after('name');
});

// Pour SUPPRIMER une colonne
Schema::table('boards', function (Blueprint $table) {
    $table->dropColumn('status');
});
```

---

## 4. Controllers

### Définition

Un **Controller** reçoit les requêtes HTTP et orchestre la réponse. Il :
- Valide les données entrantes
- Vérifie les autorisations (via Policy)
- Interagit avec les Models/Services
- Retourne une réponse (vue, redirect, JSON)

### Emplacement
```
app/Http/Controllers/
├── Controller.php           # Classe de base
├── BoardController.php      # Web
├── ColumnController.php
├── CardController.php
└── Api/                     # API
    ├── BoardController.php
    ├── ColumnController.php
    └── CardController.php
```

### Exemple : Controller Web

```php
<?php

namespace App\Http\Controllers;

use App\Models\Board;use App\Services\Model\BoardService;use Illuminate\Http\Request;use Inertia\Inertia;

class BoardController extends Controller
{
    public function __construct(
        private BoardService $boardService
    ) {}

    public function index()
    {
        $boards = $this->boardService->getAllForUser(auth()->user());

        return Inertia::render('Boards/Index', [
            'boards' => $boards
        ]);
    }

    public function show(Board $board)
    {
        $this->authorize('view', $board);

        $board = $this->boardService->getWithRelations($board);

        return Inertia::render('Boards/Show', [
            'board' => $board
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $board = $this->boardService->create(auth()->user(), $validated);

        return redirect()->route('boards.show', $board)
            ->with('success', 'Board créé !');
    }

    public function destroy(Board $board)
    {
        $this->authorize('delete', $board);

        $this->boardService->delete($board);

        return redirect()->route('boards.index')
            ->with('success', 'Board supprimé !');
    }
}
```

### Exemple : Controller API

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;use App\Http\Resources\BoardResource;use App\Models\Board;use App\Services\Model\BoardService;use Illuminate\Http\Request;use Illuminate\Http\Response;

class BoardController extends Controller
{
    public function __construct(
        private BoardService $boardService
    ) {}

    public function index(Request $request)
    {
        $boards = $this->boardService->getAllForUser($request->user());

        return BoardResource::collection($boards);
    }

    public function show(Board $board)
    {
        $this->authorize('view', $board);

        $board = $this->boardService->getWithRelations($board);

        return new BoardResource($board);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $board = $this->boardService->create($request->user(), $validated);

        return new BoardResource($board);
    }

    public function destroy(Board $board): Response
    {
        $this->authorize('delete', $board);

        $this->boardService->delete($board);

        return response()->noContent();
    }
}
```

### Différence Web vs API

| | Controller Web | Controller API |
|--|----------------|----------------|
| **Client** | Navigateur (React/Inertia) | App mobile, service externe |
| **Retourne** | Page HTML via Inertia | JSON via Resource |
| **Auth** | Session (cookie) | Token (Sanctum) |
| **Routes** | `routes/web.php` | `routes/api.php` |

### Route Model Binding
```php
// Laravel injecte automatiquement le Board
public function show(Board $board)
//                   ^^^^^^^^^^^^
// URL: /boards/5
// Laravel fait: Board::find(5)
// Si non trouvé: erreur 404 automatique
```

### Règles de validation

| Règle | Description |
|-------|-------------|
| `required` | Obligatoire |
| `string` | Doit être du texte |
| `max:255` | Maximum 255 caractères |
| `nullable` | Peut être vide |
| `email` | Format email valide |
| `exists:users,id` | Doit exister dans la table |
| `unique:boards,name` | Doit être unique |

### Commande de création
```bash
sail artisan make:controller BoardController --resource
sail artisan make:controller Api/BoardController --resource
```

---

## 5. Routes

### Définition

Les **Routes** font le lien entre une URL et un Controller. Elles définissent :
- La méthode HTTP (GET, POST, PUT, DELETE)
- L'URL
- Le Controller et la méthode à appeler
- Les middlewares à appliquer

### Emplacement
```
routes/
├── web.php        # Routes web (sessions, CSRF)
├── api.php        # Routes API (stateless, JSON)
├── channels.php   # Autorisations WebSocket
└── auth.php       # Routes authentification (Breeze)
```

### Routes Web (web.php)
```php
<?php

use App\Http\Controllers\BoardController;
use App\Http\Controllers\ColumnController;
use App\Http\Controllers\CardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return inertia('Welcome');
});

Route::middleware(['auth'])->group(function () {
    
    // CRUD complet pour Board (7 routes)
    Route::resource('boards', BoardController::class);
    
    // Columns
    Route::post('/boards/{board}/columns', [ColumnController::class, 'store'])
        ->name('columns.store');
    Route::put('/columns/{column}', [ColumnController::class, 'update'])
        ->name('columns.update');
    Route::delete('/columns/{column}', [ColumnController::class, 'destroy'])
        ->name('columns.destroy');
    
    // Cards
    Route::post('/columns/{column}/cards', [CardController::class, 'store'])
        ->name('cards.store');
    Route::put('/cards/{card}', [CardController::class, 'update'])
        ->name('cards.update');
    Route::delete('/cards/{card}', [CardController::class, 'destroy'])
        ->name('cards.destroy');
    Route::patch('/cards/{card}/move', [CardController::class, 'move'])
        ->name('cards.move');
});
```

### Routes API (api.php)
```php
<?php

use App\Http\Controllers\Api\BoardController;
use App\Http\Controllers\Api\ColumnController;
use App\Http\Controllers\Api\CardController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    
    // apiResource = resource sans create/edit (pas de formulaires)
    Route::apiResource('boards', BoardController::class);
    
    // Columns
    Route::post('boards/{board}/columns', [ColumnController::class, 'store']);
    Route::put('columns/{column}', [ColumnController::class, 'update']);
    Route::delete('columns/{column}', [ColumnController::class, 'destroy']);
    
    // Cards
    Route::post('columns/{column}/cards', [CardController::class, 'store']);
    Route::put('cards/{card}', [CardController::class, 'update']);
    Route::delete('cards/{card}', [CardController::class, 'destroy']);
    Route::patch('cards/{card}/move', [CardController::class, 'move']);
});
```

### Route::resource vs Route::apiResource

| Route::resource | Route::apiResource |
|-----------------|-------------------|
| 7 routes | 5 routes |
| Inclut create, edit | Exclut create, edit |
| Pour Web (formulaires) | Pour API (JSON) |

### Routes générées par resource

| Méthode HTTP | URL | Action | Nom |
|--------------|-----|--------|-----|
| GET | /boards | index | boards.index |
| GET | /boards/create | create | boards.create |
| POST | /boards | store | boards.store |
| GET | /boards/{board} | show | boards.show |
| GET | /boards/{board}/edit | edit | boards.edit |
| PUT/PATCH | /boards/{board} | update | boards.update |
| DELETE | /boards/{board} | destroy | boards.destroy |

### Méthodes HTTP

| Méthode | Usage |
|---------|-------|
| `GET` | Récupérer des données |
| `POST` | Créer une ressource |
| `PUT` | Remplacer entièrement une ressource |
| `PATCH` | Modifier partiellement une ressource |
| `DELETE` | Supprimer une ressource |

### Commande utile
```bash
sail artisan route:list
sail artisan route:list --path=api
```

---

## 6. Policies

### Définition

Une **Policy** centralise la logique d'autorisation. Elle répond à la question : "L'utilisateur X peut-il faire l'action Y sur la ressource Z ?"

### Avantages

| Avant (sans Policy) | Après (avec Policy) |
|---------------------|---------------------|
| Code dupliqué partout | Logique centralisée |
| Difficile à maintenir | Un seul fichier à modifier |
| Difficile à tester | Testable isolément |

### Emplacement
```
app/Policies/
├── BoardPolicy.php
├── ColumnPolicy.php
└── CardPolicy.php
```

### Exemple complet : BoardPolicy.php
```php
<?php

namespace App\Policies;

use App\Models\Board;
use App\Models\User;

class BoardPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Board $board): bool
    {
        return $user->id === $board->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Board $board): bool
    {
        return $user->id === $board->user_id;
    }

    public function delete(User $user, Board $board): bool
    {
        return $user->id === $board->user_id;
    }
}
```

### Enregistrement (AppServiceProvider.php)
```php
use App\Models\Board;
use App\Policies\BoardPolicy;
use Illuminate\Support\Facades\Gate;

public function boot(): void
{
    Gate::policy(Board::class, BoardPolicy::class);
}
```

### Utilisation dans le Controller
```php
$this->authorize('view', $board);
$this->authorize('delete', $board);
$this->authorize('create', [Column::class, $board]);
```

### Controller de base requis
```php
// app/Http/Controllers/Controller.php
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Controller
{
    use AuthorizesRequests;
}
```

### Commande de création
```bash
sail artisan make:policy BoardPolicy --model=Board
```

---

## 7. Events

### Définition

Un **Event** représente quelque chose qui s'est passé dans l'application. Il peut être :
- Écouté par des Listeners (actions locales)
- Broadcasté via WebSocket (temps réel)

### Emplacement
```
app/Events/
├── CardMoved.php
├── CardDeleted.php
├── ColumnDeleted.php
└── BoardUpdated.php
```

### Exemple complet : CardDeleted.php
```php
<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class CardDeleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public int $boardId;
    public int $cardId;

    public function __construct(int $boardId, int $cardId)
    {
        $this->boardId = $boardId;
        $this->cardId = $cardId;
    }

    // OÙ envoyer (canal WebSocket)
    public function broadcastOn(): array
    {
        return [
            new PresenceChannel('board.' . $this->boardId)
        ];
    }

    // NOM de l'événement côté JS
    public function broadcastAs(): string
    {
        return 'card.deleted';
    }

    // DONNÉES à envoyer
    public function broadcastWith(): array
    {
        return [
            'board_id' => $this->boardId,
            'card_id' => $this->cardId,
            'action' => 'deleted',
            'timestamp' => now()->toISOString()
        ];
    }
}
```

### Les 3 méthodes importantes

| Méthode | Définit | Exemple |
|---------|---------|---------|
| `broadcastOn()` | OÙ envoyer | `board.5` |
| `broadcastAs()` | NOM de l'événement | `card.deleted` |
| `broadcastWith()` | DONNÉES à envoyer | `['card_id' => 1]` |

### Utilisation
```php
broadcast(new CardDeleted($boardId, $cardId))->toOthers();
```

### Commande de création
```bash
sail artisan make:event CardDeleted
```

---

## 8. Jobs & Queues

### Définition

Un **Job** est une tâche exécutée en arrière-plan. Il permet de ne pas bloquer l'utilisateur pour des opérations longues.

### Quand utiliser ?

| Situation | Job ? |
|-----------|-------|
| Supprimer une carte | ❌ Non (instantané) |
| Importer 1000 cartes | ✅ Oui (long) |
| Envoyer un email | ✅ Oui (peut être lent) |
| Générer un PDF | ✅ Oui (long) |

### Emplacement
```
app/Jobs/
└── ImportTasksFromCsv.php
```

### Exemple complet
```php
<?php

namespace App\Jobs;

use App\Events\BoardUpdated;
use App\Models\Board;
use App\Models\Card;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ImportTasksFromCsv implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300;

    public function __construct(
        public Board $board,
        public string $filePath,
        public int $userId
    ) {}

    public function handle(): void
    {
        Log::info("Import démarré pour board {$this->board->id}");

        $column = $this->board->columns()->firstOrCreate(
            ['name' => 'Imported'],
            ['position' => $this->board->columns()->count()]
        );

        $file = Storage::get($this->filePath);
        $lines = explode("\n", $file);
        array_shift($lines);

        $position = $column->cards()->count();

        foreach ($lines as $line) {
            if (empty(trim($line))) continue;
            $data = str_getcsv($line);

            Card::create([
                'title' => $data[0],
                'description' => $data[1] ?? null,
                'column_id' => $column->id,
                'user_id' => $this->userId,
                'position' => $position++
            ]);
        }

        Storage::delete($this->filePath);
        broadcast(new BoardUpdated($this->board));

        Log::info("Import terminé");
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Import échoué: " . $exception->getMessage());
        Storage::delete($this->filePath);
    }
}
```

### Dispatch d'un Job
```php
ImportTasksFromCsv::dispatch($board, $filePath, auth()->id());
```

### Flux d'exécution
```
Controller dispatch()
        │
        ▼
┌─────────────────┐
│      REDIS      │  Stocke le job dans la queue
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│    HORIZON      │  Exécute les jobs
└────────┬────────┘
         │
         ▼
    Job::handle()
```

### Commandes
```bash
sail artisan make:job ImportTasksFromCsv
sail artisan horizon
# Dashboard: http://localhost/horizon
```

---

## 9. Broadcasting (Reverb)

### Définition

**Reverb** est le serveur WebSocket de Laravel pour le temps réel.

### Configuration (.env)
```env
BROADCAST_CONNECTION=reverb

REVERB_APP_ID=1000
REVERB_APP_KEY=your-key
REVERB_APP_SECRET=your-secret
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

### Autorisation des canaux (channels.php)
```php
use App\Models\Board;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('board.{boardId}', function ($user, $boardId) {
    $board = Board::find($boardId);
    
    if (!$board || $board->user_id !== $user->id) {
        return false;
    }
    
    return ['id' => $user->id, 'name' => $user->name];
});
```

### Types de canaux

| Type | Usage | Auth requise |
|------|-------|--------------|
| `Channel` | Public | Non |
| `PrivateChannel` | Privé | Oui |
| `PresenceChannel` | Privé + liste connectés | Oui |

### Écoute côté Frontend
```jsx
// hooks/useBoardChannel.js
import { useEffect } from 'react';
import { router } from '@inertiajs/react';

export default function useBoardChannel(boardId) {
    useEffect(() => {
        if (!window.Echo) return;

        const channel = window.Echo.join(`board.${boardId}`)
            .listen('.card.moved', () => {
                router.reload({ only: ['board'] });
            })
            .listen('.card.deleted', () => {
                router.reload({ only: ['board'] });
            })
            .listen('.board.updated', () => {
                router.reload({ only: ['board'] });
            });

        return () => {
            window.Echo.leave(`board.${boardId}`);
        };
    }, [boardId]);
}
```

### Commande
```bash
sail artisan reverb:start --debug
```

---

## 10. Frontend (Inertia + React)

### Définition

**Inertia.js** permet de créer des SPA sans API séparée.

### Structure
```
resources/js/
├── app.jsx
├── bootstrap.js
├── Components/
│   └── Board/
│       ├── Card.jsx
│       ├── Column.jsx
│       └── CardForm.jsx
├── hooks/
│   └── useBoardChannel.js
├── Layouts/
│   └── AuthenticatedLayout.jsx
└── Pages/
    └── Boards/
        ├── Index.jsx
        ├── Create.jsx
        └── Show.jsx
```

### Hooks Inertia
```jsx
import { useForm, router, Head, Link } from '@inertiajs/react';

// Formulaires
const { data, setData, post, processing, errors } = useForm({
    name: '',
});

// Navigation
router.reload({ only: ['board'] });
router.delete(route('cards.destroy', cardId));

// Head
<Head title="Mon Board" />

// Liens
<Link href={route('boards.index')}>Retour</Link>
```

---

## 11. Tests

### Types de tests

| Type | Emplacement | Usage |
|------|-------------|-------|
| Feature | `tests/Feature/` | Teste le flux complet |
| Unit | `tests/Unit/` | Teste une classe isolée |

### Exemple : BoardPolicyTest.php
```php
<?php

namespace Tests\Feature;

use App\Models\Board;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BoardPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_own_board(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($user->can('view', $board));
    }

    public function test_user_cannot_view_others_board(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $otherUser->id]);

        $this->assertFalse($user->can('view', $board));
    }

    public function test_http_access_denied_to_others_board(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)->get("/boards/{$board->id}");

        $response->assertStatus(403);
    }
}
```

### Assertions courantes

| Assertion | Vérifie |
|-----------|---------|
| `assertTrue($x)` | $x est true |
| `assertFalse($x)` | $x est false |
| `assertStatus(200)` | Code HTTP 200 |
| `assertRedirect()` | Réponse est redirection |
| `assertDatabaseHas('table', [...])` | Ligne existe |
| `assertDatabaseMissing('table', [...])` | Ligne n'existe pas |

### Commandes
```bash
sail artisan test
sail artisan test --filter=BoardPolicyTest
sail artisan test --coverage
```

---

## 12. Factories & Seeders

### Factories
```php
// database/factories/BoardFactory.php
class BoardFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'user_id' => User::factory()
        ];
    }
}
```

### Utilisation
```php
$board = Board::factory()->create();
$boards = Board::factory()->count(10)->create();
$board = Board::factory()->create(['name' => 'Mon Board']);
```

### Seeders
```php
// database/seeders/UserSeeder.php
class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => Hash::make('password')
        ]);
    }
}
```

### Commandes
```bash
sail artisan make:factory BoardFactory
sail artisan make:seeder UserSeeder
sail artisan db:seed
sail artisan migrate:fresh --seed
```

---

## 13. API Resources

### Définition

Les **API Resources** transforment les Models en JSON formaté, contrôlant exactement les données exposées.

### Emplacement
```
app/Http/Resources/
├── BoardResource.php
├── ColumnResource.php
├── CardResource.php
└── UserResource.php
```

### Exemple : BoardResource.php
```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BoardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            
            // Relations conditionnelles
            'user' => new UserResource($this->whenLoaded('user')),
            'columns' => ColumnResource::collection($this->whenLoaded('columns')),
            
            // Données calculées
            'columns_count' => $this->when(
                $this->columns_count !== null,
                $this->columns_count
            ),
        ];
    }
}
```

### Utilisation
```php
// Une ressource
return new BoardResource($board);

// Collection
return BoardResource::collection($boards);
```

### Méthodes utiles

| Méthode | Usage |
|---------|-------|
| `whenLoaded('relation')` | Inclut si relation chargée |
| `when($condition, $value)` | Inclut si condition vraie |
| `collection($items)` | Transforme une collection |

### Commande
```bash
sail artisan make:resource BoardResource
```

---

## 14. Services & Repositories

### Pourquoi ?

Éviter la duplication de code entre Controllers Web et API.

### Architecture
```
Controller Web ──┐
                 ├──► Service ──► Repository ──► Model
Controller API ──┘
```

### Service
```php
// app/Services/BoardService.php
<?php

namespace App\Services;

use App\Models\Board;
use App\Models\User;
use App\Repositories\BoardRepository;
use Illuminate\Support\Collection;

class BoardService
{
    public function __construct(
        private BoardRepository $boardRepository
    ) {}

    public function getAllForUser(User $user): Collection
    {
        return $this->boardRepository->getAllForUser($user);
    }

    public function getWithRelations(Board $board): Board
    {
        return $this->boardRepository->findByIdWithRelations($board->id);
    }

    public function create(User $user, array $data): Board
    {
        return $this->boardRepository->create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'user_id' => $user->id,
        ]);
    }

    public function update(Board $board, array $data): Board
    {
        return $this->boardRepository->update($board, $data);
    }

    public function delete(Board $board): void
    {
        $this->boardRepository->delete($board);
    }
}
```

### Repository
```php
// app/Repositories/BoardRepository.php
<?php

namespace App\Repositories;

use App\Models\Board;
use App\Models\User;
use Illuminate\Support\Collection;

class BoardRepository
{
    public function findById(int $id): ?Board
    {
        return Board::find($id);
    }

    public function findByIdWithRelations(int $id): ?Board
    {
        return Board::with(['columns.cards', 'user'])->find($id);
    }

    public function getAllForUser(User $user): Collection
    {
        return Board::where('user_id', $user->id)
            ->withCount('columns')
            ->latest()
            ->get();
    }

    public function create(array $data): Board
    {
        return Board::create($data);
    }

    public function update(Board $board, array $data): Board
    {
        $board->update($data);
        return $board->fresh();
    }

    public function delete(Board $board): void
    {
        $board->delete();
    }
}
```

### Responsabilités

| Couche | Responsabilité |
|--------|----------------|
| **Controller** | Validation, autorisation, format réponse |
| **Service** | Logique métier |
| **Repository** | Accès aux données |
| **Model** | Structure des données |

---

## 15. Architecture projet critique/fédéral

### Vue d'ensemble

Un projet critique/fédéral nécessite une architecture robuste, maintenable, testable et sécurisée.

### Évolution des architectures
```
┌─────────────────────────────────────────────────────────────────────┐
│                    ÉVOLUTION DES ARCHITECTURES                      │
└─────────────────────────────────────────────────────────────────────┘

PETIT PROJET (MVP, prototype)
──────────────────────────────
Controller → Model → Database

PROJET MOYEN (startup, PME)
──────────────────────────────
Controller → Service → Model → Database

GROS PROJET (entreprise)
──────────────────────────────
Controller → Service → Repository → Model → Database
     ↑
Form Request (validation)

PROJET CRITIQUE/FÉDÉRAL
──────────────────────────────
┌─────────────────┐
│  Form Request   │  Validation
└────────┬────────┘
         ▼
┌─────────────────┐
│   Controller    │  Orchestration (très léger)
└────────┬────────┘
         ▼
┌─────────────────┐
│     Policy      │  Autorisations
└────────┬────────┘
         ▼
┌─────────────────┐
│     Action      │  Une action = une classe
└────────┬────────┘
         ▼
┌─────────────────┐
│    Service      │  Logique métier complexe
└────────┬────────┘
         ▼
┌─────────────────┐
│   Repository    │  Accès aux données
└────────┬────────┘
         ▼
┌─────────────────┐
│     Model       │  Structure des données
└────────┬────────┘
         ▼
┌─────────────────┐
│    Database     │
└─────────────────┘
```

### Structure de dossiers projet critique
```
app/
├── Actions/                      # Une action = une classe
│   ├── Board/
│   │   ├── CreateBoard.php
│   │   ├── UpdateBoard.php
│   │   ├── DeleteBoard.php
│   │   └── ImportBoardFromCsv.php
│   ├── Column/
│   │   ├── CreateColumn.php
│   │   └── DeleteColumn.php
│   └── Card/
│       ├── CreateCard.php
│       ├── MoveCard.php
│       └── DeleteCard.php
│
├── DataTransferObjects/          # DTOs
│   ├── Board/
│   │   ├── BoardData.php
│   │   └── CreateBoardData.php
│   ├── Column/
│   │   └── ColumnData.php
│   └── Card/
│       └── CardData.php
│
├── Events/
│   ├── Board/
│   │   └── BoardUpdated.php
│   └── Card/
│       ├── CardMoved.php
│       └── CardDeleted.php
│
├── Http/
│   ├── Controllers/
│   │   ├── Web/
│   │   │   ├── BoardController.php
│   │   │   └── CardController.php
│   │   └── Api/
│   │       └── V1/               # Versioning API
│   │           ├── BoardController.php
│   │           └── CardController.php
│   ├── Requests/                 # Form Requests
│   │   ├── Board/
│   │   │   ├── StoreBoardRequest.php
│   │   │   └── UpdateBoardRequest.php
│   │   └── Card/
│   │       └── StoreCardRequest.php
│   ├── Resources/                # API Resources
│   │   └── V1/
│   │       ├── BoardResource.php
│   │       └── CardResource.php
│   └── Middleware/
│       ├── AuditLog.php          # Log des actions
│       └── RateLimiter.php
│
├── Jobs/
│   └── Board/
│       └── ImportTasksFromCsv.php
│
├── Models/
│   ├── Board.php
│   ├── Column.php
│   ├── Card.php
│   └── AuditLog.php              # Table d'audit
│
├── Policies/
│   ├── BoardPolicy.php
│   └── CardPolicy.php
│
├── Repositories/
│   ├── Contracts/                # Interfaces
│   │   ├── BoardRepositoryInterface.php
│   │   └── CardRepositoryInterface.php
│   └── Eloquent/                 # Implémentations
│       ├── BoardRepository.php
│       └── CardRepository.php
│
├── Services/
│   ├── Board/
│   │   └── BoardService.php
│   └── Card/
│       └── CardService.php
│
└── Exceptions/
    ├── BoardNotFoundException.php
    └── UnauthorizedActionException.php
```

### Composants additionnels pour projet critique

#### 1. Form Requests (Validation séparée)
```php
// app/Http/Requests/Board/StoreBoardRequest.php
<?php

namespace App\Http\Requests\Board;

use Illuminate\Foundation\Http\FormRequest;

class StoreBoardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:10000',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le nom du board est obligatoire.',
            'name.max' => 'Le nom ne peut pas dépasser 255 caractères.',
        ];
    }
}
```

**Utilisation dans le Controller :**
```php
public function store(StoreBoardRequest $request, CreateBoard $action)
{
    // Validation déjà effectuée automatiquement
    $board = $action->execute(auth()->user(), $request->validated());

    return redirect()->route('boards.show', $board);
}
```

**Importance :** Sépare la validation du Controller, réutilisable, testable.

---

#### 2. Actions (Une action = une classe)
```php
// app/Actions/Board/CreateBoard.php
<?php

namespace App\Actions\Board;

use App\DataTransferObjects\Board\CreateBoardData;
use App\Models\Board;
use App\Models\User;
use App\Repositories\Contracts\BoardRepositoryInterface;

class CreateBoard
{
    public function __construct(
        private BoardRepositoryInterface $boardRepository
    ) {}

    public function execute(User $user, array $data): Board
    {
        $boardData = CreateBoardData::fromArray($data);

        $board = $this->boardRepository->create([
            'name' => $boardData->name,
            'description' => $boardData->description,
            'user_id' => $user->id,
        ]);

        // Créer les colonnes par défaut
        $board->columns()->createMany([
            ['name' => 'À faire', 'position' => 0],
            ['name' => 'En cours', 'position' => 1],
            ['name' => 'Terminé', 'position' => 2],
        ]);

        return $board;
    }
}
```

**Importance :**
- Une classe = une responsabilité
- Facile à tester
- Réutilisable (Controller Web, API, Job, Console)

---

#### 3. Data Transfer Objects (DTOs)
```php
// app/DataTransferObjects/Board/CreateBoardData.php
<?php

namespace App\DataTransferObjects\Board;

class CreateBoardData
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $description,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            description: $data['description'] ?? null,
        );
    }

    public static function fromRequest($request): self
    {
        return new self(
            name: $request->input('name'),
            description: $request->input('description'),
        );
    }
}
```

**Importance :**
- Type safety (readonly)
- Structure claire des données
- Validation implicite
- Documentation du code

---

#### 4. Interfaces Repository
```php
// app/Repositories/Contracts/BoardRepositoryInterface.php
<?php

namespace App\Repositories\Contracts;

use App\Models\Board;
use App\Models\User;
use Illuminate\Support\Collection;

interface BoardRepositoryInterface
{
    public function findById(int $id): ?Board;
    public function findByIdWithRelations(int $id): ?Board;
    public function getAllForUser(User $user): Collection;
    public function create(array $data): Board;
    public function update(Board $board, array $data): Board;
    public function delete(Board $board): void;
}
```
```php
// app/Repositories/Eloquent/BoardRepository.php
<?php

namespace App\Repositories\Eloquent;

use App\Models\Board;
use App\Models\User;
use App\Repositories\Contracts\BoardRepositoryInterface;
use Illuminate\Support\Collection;

class BoardRepository implements BoardRepositoryInterface
{
    public function findById(int $id): ?Board
    {
        return Board::find($id);
    }

    public function findByIdWithRelations(int $id): ?Board
    {
        return Board::with(['columns.cards', 'user'])->find($id);
    }

    public function getAllForUser(User $user): Collection
    {
        return Board::where('user_id', $user->id)
            ->withCount('columns')
            ->latest()
            ->get();
    }

    public function create(array $data): Board
    {
        return Board::create($data);
    }

    public function update(Board $board, array $data): Board
    {
        $board->update($data);
        return $board->fresh();
    }

    public function delete(Board $board): void
    {
        $board->delete();
    }
}
```

**Binding dans AppServiceProvider :**
```php
use App\Repositories\Contracts\BoardRepositoryInterface;
use App\Repositories\Eloquent\BoardRepository;

public function register(): void
{
    $this->app->bind(BoardRepositoryInterface::class, BoardRepository::class);
}
```

**Importance :**
- Abstraction de la source de données
- Permet de changer d'implémentation (Eloquent → API externe)
- Testable avec des mocks

---

#### 5. Exceptions personnalisées
```php
// app/Exceptions/BoardNotFoundException.php
<?php

namespace App\Exceptions;

use Exception;

class BoardNotFoundException extends Exception
{
    public function __construct(int $boardId)
    {
        parent::__construct("Board with ID {$boardId} not found.");
    }

    public function render($request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Board not found',
                'message' => $this->getMessage()
            ], 404);
        }

        return redirect()->route('boards.index')
            ->with('error', 'Board introuvable.');
    }
}
```

**Importance :**
- Messages d'erreur clairs
- Comportement différent selon le contexte (Web/API)
- Logging centralisé

---

#### 6. Audit Log (Traçabilité)
```php
// Migration
Schema::create('audit_logs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->nullable()->constrained();
    $table->string('action');           // created, updated, deleted
    $table->string('model_type');       // App\Models\Board
    $table->unsignedBigInteger('model_id');
    $table->json('old_values')->nullable();
    $table->json('new_values')->nullable();
    $table->string('ip_address')->nullable();
    $table->string('user_agent')->nullable();
    $table->timestamps();
});
```
```php
// app/Models/AuditLog.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id', 'action', 'model_type', 'model_id',
        'old_values', 'new_values', 'ip_address', 'user_agent'
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
```
```php
// Trait pour les Models
// app/Traits/Auditable.php
<?php

namespace App\Traits;

use App\Models\AuditLog;

trait Auditable
{
    protected static function bootAuditable()
    {
        static::created(function ($model) {
            self::audit('created', $model, null, $model->toArray());
        });

        static::updated(function ($model) {
            self::audit('updated', $model, $model->getOriginal(), $model->toArray());
        });

        static::deleted(function ($model) {
            self::audit('deleted', $model, $model->toArray(), null);
        });
    }

    protected static function audit($action, $model, $oldValues, $newValues)
    {
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'model_type' => get_class($model),
            'model_id' => $model->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
```
```php
// Utilisation dans le Model
class Board extends Model
{
    use HasFactory, Auditable;
    // ...
}
```

**Importance pour projet fédéral :**
- Traçabilité complète (qui a fait quoi, quand)
- Requis pour audits de sécurité
- Conformité réglementaire (RGPD, etc.)

---

#### 7. API Versioning
```php
// routes/api.php
Route::prefix('v1')->group(function () {
    Route::middleware('auth:sanctum')->group(function () {
        Route::apiResource('boards', Api\V1\BoardController::class);
    });
});

Route::prefix('v2')->group(function () {
    Route::middleware('auth:sanctum')->group(function () {
        Route::apiResource('boards', Api\V2\BoardController::class);
    });
});
```

**Importance :**
- Rétrocompatibilité
- Permet de faire évoluer l'API sans casser les clients existants

---

#### 8. Rate Limiting
```php
// app/Providers/RouteServiceProvider.php
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

protected function configureRateLimiting(): void
{
    RateLimiter::for('api', function (Request $request) {
        return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
    });

    RateLimiter::for('critical', function (Request $request) {
        return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
    });
}
```
```php
// Utilisation dans les routes
Route::middleware(['auth:sanctum', 'throttle:critical'])->group(function () {
    Route::delete('boards/{board}', [BoardController::class, 'destroy']);
});
```

**Importance :**
- Protection contre les abus
- Protection contre les attaques DDoS

---

### Comparaison : Notre projet vs Projet critique

| Aspect | Notre projet | Projet critique |
|--------|--------------|-----------------|
| **Validation** | Dans Controller | Form Request séparé |
| **Logique métier** | Service | Action + Service |
| **Accès données** | Model direct | Repository + Interface |
| **Données** | Arrays | DTOs typés |
| **Erreurs** | Exceptions Laravel | Exceptions personnalisées |
| **Traçabilité** | ❌ | Audit Log complet |
| **API** | Une version | Versioning (v1, v2) |
| **Rate Limiting** | Basique | Personnalisé par endpoint |
| **Tests** | Feature tests | Feature + Unit + Integration |

---

### Checklist projet critique/fédéral

#### Architecture

- [ ] Form Requests pour toute validation
- [ ] Actions pour chaque cas d'usage
- [ ] Services pour logique métier complexe
- [ ] Repositories avec interfaces
- [ ] DTOs pour les données structurées
- [ ] Exceptions personnalisées

#### Sécurité

- [ ] Policies pour toutes les autorisations
- [ ] Rate limiting sur les endpoints sensibles
- [ ] Audit log de toutes les actions
- [ ] Validation stricte des entrées
- [ ] Sanitisation des sorties
- [ ] HTTPS obligatoire
- [ ] Headers de sécurité (CSP, HSTS, etc.)

#### Qualité

- [ ] Tests unitaires (>80% couverture)
- [ ] Tests d'intégration
- [ ] Tests E2E (Cypress/Playwright)
- [ ] Documentation API (OpenAPI/Swagger)
- [ ] Documentation technique
- [ ] Code review obligatoire
- [ ] CI/CD pipeline

#### Monitoring

- [ ] Logging centralisé
- [ ] Alertes sur erreurs
- [ ] Métriques de performance
- [ ] Health checks
- [ ] Dashboard de monitoring

#### Conformité

- [ ] RGPD (si données personnelles)
- [ ] Accessibilité (WCAG)
- [ ] Backup automatisé
- [ ] Plan de reprise d'activité
- [ ] Documentation des processus

---

## 16. Docker & Sail

### Services (docker-compose.yml)

| Service | Rôle | Port |
|---------|------|------|
| laravel.test | Application | 80 |
| pgsql | PostgreSQL | 5432 |
| redis | Cache/Queues | 6379 |
| reverb | WebSocket | 8080 |

### Commandes Sail
```bash
sail up -d          # Démarrer
sail down           # Arrêter
sail logs           # Voir logs
sail shell          # Accéder au container
sail artisan ...    # Commandes Artisan
sail composer ...   # Commandes Composer
sail npm ...        # Commandes npm
```

---

## 17. Commandes essentielles

### Développement quotidien
```bash
sail up -d
sail npm run dev
sail artisan reverb:start --debug
sail artisan horizon
```

### Création de composants
```bash
# Model + Migration + Factory
sail artisan make:model Board -mf

# Controller
sail artisan make:controller BoardController --resource
sail artisan make:controller Api/V1/BoardController --resource

# Autres
sail artisan make:policy BoardPolicy --model=Board
sail artisan make:event CardDeleted
sail artisan make:job ImportTasksFromCsv
sail artisan make:test BoardTest
sail artisan make:request StoreBoardRequest
sail artisan make:resource BoardResource
```

### Debug
```bash
sail artisan route:list
sail artisan pail
sail artisan tinker
```

---

## 18. Diagramme des relations
```
┌─────────────────────────────────────────────────────────────────────────────┐
│                     ARCHITECTURE PROJET CRITIQUE                            │
└─────────────────────────────────────────────────────────────────────────────┘

    HTTP Request
         │
         ▼
┌─────────────────┐
│   Form Request  │  Validation des données
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│   Controller    │  Orchestration (très léger)
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│     Policy      │  Autorisations
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│     Action      │  Une action = une classe
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│    Service      │  Logique métier
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│   Repository    │  Accès aux données (interface)
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│     Model       │  ORM Eloquent
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│    Database     │  PostgreSQL
└─────────────────┘


┌─────────────────────────────────────────────────────────────────────────────┐
│                          FLUX COMPLET                                       │
└─────────────────────────────────────────────────────────────────────────────┘

Navigateur ──► Routes ──► Middleware ──► Controller
                                              │
                         ┌────────────────────┼────────────────────┐
                         │                    │                    │
                         ▼                    ▼                    ▼
                    Form Request          Policy              Action/Service
                    (validation)       (autorisation)        (logique métier)
                         │                    │                    │
                         └────────────────────┼────────────────────┘
                                              │
                                              ▼
                                         Repository
                                              │
                                              ▼
                                           Model
                                              │
                                              ▼
                                          Database
                                              │
                                              ▼
                                    ┌─────────────────┐
                                    │ Response:       │
                                    │ - Web: Inertia  │
                                    │ - API: Resource │
                                    └─────────────────┘


┌─────────────────────────────────────────────────────────────────────────────┐
│                         FICHIERS PAR COMPOSANT                              │
└─────────────────────────────────────────────────────────────────────────────┘

   COMPOSANT           EMPLACEMENT                         COMMANDE

   Model               app/Models/                         make:model -mf
   Migration           database/migrations/                make:migration
   Controller Web      app/Http/Controllers/               make:controller -r
   Controller API      app/Http/Controllers/Api/V1/        make:controller -r
   Form Request        app/Http/Requests/                  make:request
   API Resource        app/Http/Resources/                 make:resource
   Policy              app/Policies/                       make:policy
   Event               app/Events/                         make:event
   Job                 app/Jobs/                           make:job
   Action              app/Actions/                        (manuel)
   Service             app/Services/                       (manuel)
   Repository          app/Repositories/                   (manuel)
   DTO                 app/DataTransferObjects/            (manuel)
   Factory             database/factories/                 make:factory
   Seeder              database/seeders/                   make:seeder
   Test                tests/Feature/                      make:test


┌─────────────────────────────────────────────────────────────────────────────┐
│                         RELATIONS MODELS                                    │
└─────────────────────────────────────────────────────────────────────────────┘

   User
     │
     │ hasMany
     ▼
   Board ◄─────────── belongsTo
     │
     │ hasMany
     ▼
   Column ◄────────── belongsTo
     │
     │ hasMany
     ▼
   Card ◄──────────── belongsTo
```

---

## Résumé

| Composant | Rôle |
|-----------|------|
| **Model** | Représente une table, relations |
| **Migration** | Structure des tables |
| **Controller** | Orchestration requête/réponse |
| **Route** | URL → Controller |
| **Policy** | Autorisations |
| **Event** | Notification temps réel |
| **Job** | Tâche arrière-plan |
| **Form Request** | Validation séparée |
| **API Resource** | Formatage JSON |
| **Service** | Logique métier |
| **Repository** | Accès aux données |
| **Action** | Une action = une classe |
| **DTO** | Données typées |
