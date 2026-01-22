# Architecture Taskboard - Schéma Relationnel Complet

## Flux complet : Créer un Board (POST /api/boards)

```
┌─────────────────────────────────────────────────────────────────────────────────────────────────────┐
│                                            CLIENT                                                   │
│                                                                                                     │
│  POST /api/boards                                                                                   │
│  Headers: Authorization: Bearer TOKEN, Accept: application/json                                     │
│  Body: { "name": "Mon Board", "description": "Description" }                                        │
└─────────────────────────────────────────────────────────────────────────────────────────────────────┘
                                                │
                                                ▼
┌─────────────────────────────────────────────────────────────────────────────────────────────────────┐
│                                         ROUTES                                                      │
│                                     routes/api.php                                                  │
├─────────────────────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                                     │
│  Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {                          │
│      Route::post('boards', [BoardController::class, 'store'])                                       │
│          ->middleware('throttle:api-write')                                                         │
│          ->name('api.boards.store');                                                                │
│  });                                                                                                │
│                                                                                                     │
│  Middlewares appliqués (dans l'ordre):                                                              │
│  1. auth:sanctum                                                                                    │
│  2. throttle:api                                                                                    │
│  3. throttle:api-write                                                                              │
│                                                                                                     │
└─────────────────────────────────────────────────────────────────────────────────────────────────────┘
                                                │
                                                ▼
┌─────────────────────────────────────────────────────────────────────────────────────────────────────┐
│                                      MIDDLEWARES                                                    │
├─────────────────────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                                     │
│  ┌─────────────────────────────────────────────────────────────────────────────────────────────┐   │
│  │  1. auth:sanctum (Laravel\Sanctum)                                                          │   │
│  │     └── Vérifie le token Bearer dans personal_access_tokens                                 │   │
│  │     └── Échec → 401 Unauthorized                                                            │   │
│  │     └── Succès → Attache $request->user() et continue                                       │   │
│  └─────────────────────────────────────────────────────────────────────────────────────────────┘   │
│                                          │                                                          │
│                                          ▼                                                          │
│  ┌─────────────────────────────────────────────────────────────────────────────────────────────┐   │
│  │  2. throttle:api (RateLimiter)                                                              │   │
│  │     └── Défini dans bootstrap/app.php                                                       │   │
│  │     └── Admin: 120/min, User: 60/min, Anonyme: 30/min                                       │   │
│  │     └── Échec → 429 Too Many Requests                                                       │   │
│  │     └── Succès → Continue                                                                   │   │
│  └─────────────────────────────────────────────────────────────────────────────────────────────┘   │
│                                          │                                                          │
│                                          ▼                                                          │
│  ┌─────────────────────────────────────────────────────────────────────────────────────────────┐   │
│  │  3. throttle:api-write (RateLimiter)                                                        │   │
│  │     └── Admin: 60/min, User: 20/min, Anonyme: 5/min                                         │   │
│  │     └── Échec → 429 Too Many Requests                                                       │   │
│  │     └── Succès → Continue                                                                   │   │
│  └─────────────────────────────────────────────────────────────────────────────────────────────┘   │
│                                          │                                                          │
│                                          ▼                                                          │
│  ┌─────────────────────────────────────────────────────────────────────────────────────────────┐   │
│  │  4. LogActivityContext (Custom)                                                             │   │
│  │     └── app/Http/Middleware/LogActivityContext.php                                          │   │
│  │     └── Attache IP et User-Agent pour l'audit log                                           │   │
│  └─────────────────────────────────────────────────────────────────────────────────────────────┘   │
│                                                                                                     │
└─────────────────────────────────────────────────────────────────────────────────────────────────────┘
                                                │
                                                ▼
┌─────────────────────────────────────────────────────────────────────────────────────────────────────┐
│                                       CONTROLLER                                                    │
│                           app/Http/Controllers/Api/BoardController.php                              │
├─────────────────────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                                     │
│  class BoardController extends Controller                                                           │
│  {                                                                                                  │
│      public function __construct(                                                                   │
│          private readonly BoardService $boardService  ◄─── Injection de dépendance                 │
│      ) {}                                                                                           │
│                                                                                                     │
│      public function store(Request $request): BoardResource                                         │
│      {                                                                                              │
│          // 1. Transformer Request → DTO (avec validation automatique)                              │
│          $data = BoardData::from($request);  ──────────────────────────────────┐                    │
│                                                                                │                    │
│          // 2. Appeler le Service avec le DTO                                  │                    │
│          $board = $this->boardService->create($request->user(), $data); ───┐   │                    │
│                                                                            │   │                    │
│          // 3. Retourner la Resource (format JSON)                         │   │                    │
│          return new BoardResource($board);  ───────────────────────────┐   │   │                    │
│      }                                                                 │   │   │                    │
│  }                                                                     │   │   │                    │
│                                                                        │   │   │                    │
└────────────────────────────────────────────────────────────────────────│───│───│────────────────────┘
                                                                         │   │   │
                    ┌────────────────────────────────────────────────────┘   │   │
                    │                    ┌───────────────────────────────────┘   │
                    │                    │                    ┌─────────────────┘
                    ▼                    ▼                    ▼
┌──────────────────────────┐ ┌────────────────────────┐ ┌────────────────────────────────────────────┐
│       RESOURCE           │ │         DTO            │ │                SERVICE                     │
│ app/Http/Resources/      │ │  app/Data/Board/       │ │       app/Services/Model/                  │
│   BoardResource.php      │ │    BoardData.php       │ │         BoardService.php                   │
├──────────────────────────┤ ├────────────────────────┤ ├────────────────────────────────────────────┤
│                          │ │                        │ │                                            │
│ Transforme le Model      │ │ class BoardData        │ │ class BoardService                         │
│ en JSON pour l'API       │ │   extends Data         │ │ {                                          │
│                          │ │ {                      │ │   public function __construct(             │
│ return [                 │ │   public function      │ │     private readonly                       │
│   'id' => $this->id,     │ │     __construct(       │ │       BoardRepositoryInterface             │
│   'name' => $this->name, │ │     #[Required]        │ │         $boardRepository,  ◄─── Interface  │
│   'description' => ...,  │ │     public string      │ │     private readonly                       │
│   'columns' => ...,      │ │       $name,           │ │       NotificationService                  │
│   'created_at' => ...,   │ │     public ?string     │ │         $notificationService               │
│ ];                       │ │       $description,    │ │   ) {}                                     │
│                          │ │   ) {}                 │ │                                            │
│                          │ │ }                      │ │   public function create(                  │
│                          │ │                        │ │     User $user,                            │
│                          │ │ Validation automatique │ │     BoardData $data  ◄─── DTO              │
│                          │ │ via attributs PHP 8    │ │   ): Board                                 │
│                          │ │                        │ │   {                                        │
│                          │ │ Échec validation       │ │     return DB::transaction(...);           │
│                          │ │ → 422 Unprocessable    │ │   }                                        │
│                          │ │                        │ │ }                                          │
└──────────────────────────┘ └────────────────────────┘ └────────────────────────────────────────────┘
                                                                         │
                                                                         ▼
┌─────────────────────────────────────────────────────────────────────────────────────────────────────┐
│                                         SERVICE (détail)                                            │
│                                   app/Services/Model/BoardService.php                               │
├─────────────────────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                                     │
│  public function create(User $user, BoardData $data): Board                                         │
│  {                                                                                                  │
│      return DB::transaction(function () use ($user, $data) {  ◄─── Transaction (tout ou rien)      │
│                                                                                                     │
│          // Appelle le Repository pour créer le Board                                               │
│          $board = $this->boardRepository->create([      ──────────────────────────────────┐         │
│              'name' => $data->name,           ◄─── Extrait du DTO                        │         │
│              'description' => $data->description,                                        │         │
│              'user_id' => $user->id,                                                     │         │
│          ]);                                                                             │         │
│                                                                                          │         │
│          // Logique métier: créer colonnes par défaut                                    │         │
│          $board->columns()->createMany([                                                 │         │
│              ['name' => 'À faire', 'position' => 0],                                     │         │
│              ['name' => 'En cours', 'position' => 1],                                    │         │
│              ['name' => 'Terminé', 'position' => 2],                                     │         │
│          ]);                                                                             │         │
│                                                                                          │         │
│          return $board;                                                                  │         │
│      });                                                                                 │         │
│  }                                                                                       │         │
│                                                                                          │         │
└──────────────────────────────────────────────────────────────────────────────────────────│─────────┘
                                                                                           │
                                                                                           ▼
┌─────────────────────────────────────────────────────────────────────────────────────────────────────┐
│                                    REPOSITORY INTERFACE                                             │
│                           app/Repositories/Contracts/BoardRepositoryInterface.php                   │
├─────────────────────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                                     │
│  interface BoardRepositoryInterface                ◄─── CONTRAT (pas d'implémentation)             │
│  {                                                                                                  │
│      public function findById(int $id): ?Board;                                                     │
│      public function findByIdWithRelations(int $id): Board;                                         │
│      public function getAll(): Collection;                                                          │
│      public function getAllForUser(User $user): Collection;                                         │
│      public function create(array $data): Board;   ◄─── Méthode utilisée                           │
│      public function update(Board $board, array $data): Board;                                      │
│      public function delete(Board $board): void;                                                    │
│  }                                                                                                  │
│                                                                                                     │
└─────────────────────────────────────────────────────────────────────────────────────────────────────┘
                                                │
                                                │  Binding (AppServiceProvider)
                                                │  $this->app->bind(
                                                │      BoardRepositoryInterface::class,
                                                │      BoardRepository::class
                                                │  );
                                                ▼
┌─────────────────────────────────────────────────────────────────────────────────────────────────────┐
│                                   REPOSITORY IMPLEMENTATION                                         │
│                              app/Repositories/Eloquent/BoardRepository.php                          │
├─────────────────────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                                     │
│  class BoardRepository implements BoardRepositoryInterface                                          │
│  {                                                                                                  │
│      public function create(array $data): Board                                                     │
│      {                                                                                              │
│          // Crée via Eloquent                                                                       │
│          $board = Board::create($data);  ────────────────────────────────────────┐                  │
│                                                                                  │                  │
│          // Invalide le cache                                                    │                  │
│          $this->clearUserCache($data['user_id']);                                │                  │
│                                                                                  │                  │
│          return $board;                                                          │                  │
│      }                                                                           │                  │
│                                                                                  │                  │
│      private function clearUserCache(int $userId): void                          │                  │
│      {                                                                           │                  │
│          Cache::forget("user.{$userId}.boards");  ◄─── Invalidation Redis        │                  │
│          Cache::forget('boards.all');                                            │                  │
│      }                                                                           │                  │
│  }                                                                               │                  │
│                                                                                  │                  │
└──────────────────────────────────────────────────────────────────────────────────│──────────────────┘
                                                                                   │
                                                                                   ▼
┌─────────────────────────────────────────────────────────────────────────────────────────────────────┐
│                                           MODEL                                                     │
│                                      app/Models/Board.php                                           │
├─────────────────────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                                     │
│  class Board extends Model                                                                          │
│  {                                                                                                  │
│      use HasFactory, LogsActivity;  ◄─── Trait pour Audit Log automatique                          │
│                                                                                                     │
│      protected $fillable = ['name', 'description', 'user_id'];  ◄─── Champs autorisés              │
│                                                                                                     │
│      // Configuration Audit Log                                                                     │
│      public function getActivitylogOptions(): LogOptions                                            │
│      {                                                                                              │
│          return LogOptions::defaults()                                                              │
│              ->logOnly(['name', 'description'])                                                     │
│              ->logOnlyDirty();                                                                      │
│      }                                                                                              │
│                                                                                                     │
│      // Relations                                                                                   │
│      public function user(): BelongsTo { ... }                                                      │
│      public function columns(): HasMany { ... }                                                     │
│  }                                                                                                  │
│                                                                                                     │
│  Board::create($data) utilise Eloquent pour:                                                        │
│  1. Vérifier $fillable                                                                              │
│  2. Générer: INSERT INTO boards (name, description, user_id) VALUES (...)                           │
│  3. Retourner l'objet Board hydraté avec l'ID                                                       │
│  4. Déclencher LogsActivity → Enregistre dans activity_log                                          │
│                                                                                                     │
└─────────────────────────────────────────────────────────────────────────────────────────────────────┘
                                                │
                                                ▼
┌─────────────────────────────────────────────────────────────────────────────────────────────────────┐
│                                          DATABASE                                                   │
│                                         PostgreSQL                                                  │
├─────────────────────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                                     │
│  ┌─────────────────────────────────────────────────────────────────────────────────────────────┐   │
│  │  TABLE: boards                                                                              │   │
│  │  INSERT INTO boards (name, description, user_id, created_at, updated_at)                    │   │
│  │  VALUES ('Mon Board', 'Description', 1, NOW(), NOW())                                       │   │
│  │  RETURNING id;                                                                              │   │
│  └─────────────────────────────────────────────────────────────────────────────────────────────┘   │
│                                                                                                     │
│  ┌─────────────────────────────────────────────────────────────────────────────────────────────┐   │
│  │  TABLE: columns (créées par le Service dans la transaction)                                 │   │
│  │  INSERT INTO columns (name, position, board_id, ...) VALUES                                 │   │
│  │    ('À faire', 0, 1, ...),                                                                  │   │
│  │    ('En cours', 1, 1, ...),                                                                 │   │
│  │    ('Terminé', 2, 1, ...);                                                                  │   │
│  └─────────────────────────────────────────────────────────────────────────────────────────────┘   │
│                                                                                                     │
│  ┌─────────────────────────────────────────────────────────────────────────────────────────────┐   │
│  │  TABLE: activity_log (via LogsActivity trait)                                               │   │
│  │  INSERT INTO activity_log (log_name, description, subject_type, subject_id, causer_id, ...) │   │
│  │  VALUES ('default', 'Board created', 'App\Models\Board', 1, 1, ...);                        │   │
│  └─────────────────────────────────────────────────────────────────────────────────────────────┘   │
│                                                                                                     │
└─────────────────────────────────────────────────────────────────────────────────────────────────────┘
                                                │
                                                ▼
┌─────────────────────────────────────────────────────────────────────────────────────────────────────┐
│                                           CACHE                                                     │
│                                           Redis                                                     │
├─────────────────────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                                     │
│  Après création, le Repository invalide:                                                            │
│                                                                                                     │
│  DEL laravel_cache:user.1.boards    ◄─── Liste des boards de l'utilisateur                         │
│  DEL laravel_cache:boards.all       ◄─── Liste globale (pour admin)                                │
│                                                                                                     │
│  Prochaine requête GET /api/boards:                                                                 │
│  - Cache miss → Requête SQL → Stocke en cache → Retourne                                           │
│                                                                                                     │
└─────────────────────────────────────────────────────────────────────────────────────────────────────┘
                                                │
                                                │  Remonte la chaîne
                                                ▼
┌─────────────────────────────────────────────────────────────────────────────────────────────────────┐
│                                         RÉPONSE                                                     │
├─────────────────────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                                     │
│  HTTP/1.1 201 Created                                                                               │
│  Content-Type: application/json                                                                     │
│  X-RateLimit-Limit: 20                                                                              │
│  X-RateLimit-Remaining: 19                                                                          │
│                                                                                                     │
│  {                                                                                                  │
│      "data": {                                      ◄─── Formaté par BoardResource                  │
│          "id": 1,                                                                                   │
│          "name": "Mon Board",                                                                       │
│          "description": "Description",                                                              │
│          "columns": [                                                                               │
│              { "id": 1, "name": "À faire", "position": 0 },                                         │
│              { "id": 2, "name": "En cours", "position": 1 },                                        │
│              { "id": 3, "name": "Terminé", "position": 2 }                                          │
│          ],                                                                                         │
│          "created_at": "2024-01-15T14:32:00.000000Z",                                               │
│          "updated_at": "2024-01-15T14:32:00.000000Z"                                                │
│      }                                                                                              │
│  }                                                                                                  │
│                                                                                                     │
└─────────────────────────────────────────────────────────────────────────────────────────────────────┘


═══════════════════════════════════════════════════════════════════════════════════════════════════════
                                    COMPOSANTS TRANSVERSAUX
═══════════════════════════════════════════════════════════════════════════════════════════════════════


┌─────────────────────────────────────────────────────────────────────────────────────────────────────┐
│                                    AppServiceProvider                                               │
│                                app/Providers/AppServiceProvider.php                                 │
├─────────────────────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                                     │
│  Bindings Interface → Implémentation:                                                               │
│                                                                                                     │
│  $this->app->bind(BoardRepositoryInterface::class, BoardRepository::class);                         │
│  $this->app->bind(ColumnRepositoryInterface::class, ColumnRepository::class);                       │
│  $this->app->bind(CardRepositoryInterface::class, CardRepository::class);                           │
│                                                                                                     │
│  Permet au Service de demander une Interface et recevoir l'Implémentation concrète.                 │
│                                                                                                     │
└─────────────────────────────────────────────────────────────────────────────────────────────────────┘


┌─────────────────────────────────────────────────────────────────────────────────────────────────────┐
│                                      POLICY                                                         │
│                                app/Policies/BoardPolicy.php                                         │
├─────────────────────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                                     │
│  Pour store(), pas de Policy car l'utilisateur crée son propre board.                               │
│                                                                                                     │
│  Pour update/delete, le Controller appelle:                                                         │
│  $this->authorize('update', $board);                                                                │
│                                                                                                     │
│  BoardPolicy vérifie:                                                                               │
│  - L'utilisateur a-t-il la permission ? (via Spatie Permission)                                     │
│  - Est-il propriétaire ou admin ?                                                                   │
│                                                                                                     │
└─────────────────────────────────────────────────────────────────────────────────────────────────────┘


┌─────────────────────────────────────────────────────────────────────────────────────────────────────┐
│                                   EXCEPTIONS                                                        │
│                                app/Exceptions/                                                      │
├─────────────────────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                                     │
│  Si une erreur survient:                                                                            │
│                                                                                                     │
│  throw new BoardNotFoundException($id);                                                             │
│       │                                                                                             │
│       ▼                                                                                             │
│  BaseException::render()                                                                            │
│       │                                                                                             │
│       ▼                                                                                             │
│  {                                                                                                  │
│      "error": {                                                                                     │
│          "type": "board_not_found",                                                                 │
│          "message": "Board with ID 99 not found.",                                                  │
│          "details": { "board_id": 99 }                                                              │
│      }                                                                                              │
│  }                                                                                                  │
│       │                                                                                             │
│       ▼                                                                                             │
│  Enregistré dans activity_log (via bootstrap/app.php withExceptions)                                │
│                                                                                                     │
└─────────────────────────────────────────────────────────────────────────────────────────────────────┘


┌─────────────────────────────────────────────────────────────────────────────────────────────────────┐
│                              ROLES & PERMISSIONS                                                    │
│                               Spatie Laravel Permission                                             │
├─────────────────────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                                     │
│  Tables:                                                                                            │
│  - roles (admin, viewer)                                                                            │
│  - permissions (boards.create, boards.delete, etc.)                                                 │
│  - role_has_permissions                                                                             │
│  - model_has_roles                                                                                  │
│                                                                                                     │
│  Utilisé par:                                                                                       │
│  - Policy: $user->hasRole('admin')                                                                  │
│  - RateLimiter: $user->hasRole('admin') → limite plus élevée                                        │
│  - Controller: $user->hasPermissionTo('boards.delete')                                              │
│                                                                                                     │
└─────────────────────────────────────────────────────────────────────────────────────────────────────┘


═══════════════════════════════════════════════════════════════════════════════════════════════════════
                                         RÉSUMÉ DES COUCHES
═══════════════════════════════════════════════════════════════════════════════════════════════════════


┌──────────────────────┬───────────────────────────────────────────────────────────────────────────────┐
│       COUCHE         │                              RESPONSABILITÉ                                   │
├──────────────────────┼───────────────────────────────────────────────────────────────────────────────┤
│ Routes               │ Diriger URL → Controller, définir les middlewares                            │
├──────────────────────┼───────────────────────────────────────────────────────────────────────────────┤
│ Middleware           │ Filtrer les requêtes (auth, throttle, csrf, log)                             │
├──────────────────────┼───────────────────────────────────────────────────────────────────────────────┤
│ Controller           │ Recevoir la requête, transformer en DTO, appeler Service, retourner Resource │
├──────────────────────┼───────────────────────────────────────────────────────────────────────────────┤
│ DTO                  │ Valider et typer les données entrantes                                       │
├──────────────────────┼───────────────────────────────────────────────────────────────────────────────┤
│ Resource             │ Formater la réponse JSON                                                     │
├──────────────────────┼───────────────────────────────────────────────────────────────────────────────┤
│ Service              │ Logique métier, orchestration, transactions                                  │
├──────────────────────┼───────────────────────────────────────────────────────────────────────────────┤
│ Repository Interface │ Contrat d'accès aux données                                                  │
├──────────────────────┼───────────────────────────────────────────────────────────────────────────────┤
│ Repository Eloquent  │ Implémentation accès données + cache                                         │
├──────────────────────┼───────────────────────────────────────────────────────────────────────────────┤
│ Model                │ Structure des données, relations, traits (Audit)                             │
├──────────────────────┼───────────────────────────────────────────────────────────────────────────────┤
│ Policy               │ Autorisation (peut-il faire cette action ?)                                  │
├──────────────────────┼───────────────────────────────────────────────────────────────────────────────┤
│ Exception            │ Gestion des erreurs avec contexte                                            │
├──────────────────────┼───────────────────────────────────────────────────────────────────────────────┤
│ Database             │ Stockage persistant (PostgreSQL)                                             │
├──────────────────────┼───────────────────────────────────────────────────────────────────────────────┤
│ Cache                │ Stockage rapide (Redis)                                                      │
├──────────────────────┼───────────────────────────────────────────────────────────────────────────────┤
│ Audit Log            │ Traçabilité des actions                                                      │
└──────────────────────┴───────────────────────────────────────────────────────────────────────────────┘


═══════════════════════════════════════════════════════════════════════════════════════════════════════
                                      STRUCTURE DES DOSSIERS
═══════════════════════════════════════════════════════════════════════════════════════════════════════


app/
├── Console/
│   └── Commands/
│       ├── AssignRole.php
│       ├── AuditLogClean.php
│       ├── AuditLogView.php
│       ├── CleanImportFiles.php
│       ├── CreateUser.php
│       └── PurgeSanctumTokens.php
│
├── Data/                              ◄─── DTOs (Spatie Laravel Data)
│   ├── Board/
│   │   └── BoardData.php
│   ├── Card/
│   │   ├── CardData.php
│   │   └── MoveCardData.php
│   └── Column/
│       └── ColumnData.php
│
├── Events/                            ◄─── Événements Broadcasting
│   ├── CardDeleted.php
│   ├── CardMoved.php
│   └── ColumnDeleted.php
│
├── Exceptions/                        ◄─── Exceptions personnalisées
│   ├── BaseException.php
│   ├── ForbiddenException.php
│   ├── NotFoundException.php
│   ├── UnauthorizedActionException.php
│   ├── Board/
│   │   └── BoardNotFoundException.php
│   ├── Card/
│   │   ├── CannotMoveCardException.php
│   │   └── CardNotFoundException.php
│   └── Column/
│       └── ColumnNotFoundException.php
│
├── Http/
│   ├── Controllers/
│   │   ├── Api/                       ◄─── Controllers API
│   │   │   ├── AuditLogController.php
│   │   │   ├── BoardController.php
│   │   │   ├── CardController.php
│   │   │   ├── ColumnController.php
│   │   │   └── NotificationController.php
│   │   └── BoardController.php        ◄─── Controller Web
│   │
│   ├── Middleware/
│   │   ├── HandleInertiaRequests.php
│   │   └── LogActivityContext.php
│   │
│   └── Resources/                     ◄─── API Resources
│       ├── AuditLogResource.php
│       ├── BoardResource.php
│       ├── CardResource.php
│       ├── ColumnResource.php
│       └── NotificationResource.php
│
├── Models/                            ◄─── Eloquent Models
│   ├── Board.php
│   ├── Card.php
│   ├── Column.php
│   └── User.php
│
├── Notifications/                     ◄─── Notifications
│   ├── BoardSharedNotification.php
│   ├── CardAssignedNotification.php
│   └── CardMovedNotification.php
│
├── Policies/                          ◄─── Policies (autorisation)
│   ├── BoardPolicy.php
│   ├── CardPolicy.php
│   └── ColumnPolicy.php
│
├── Providers/
│   ├── AppServiceProvider.php         ◄─── Bindings
│   └── HealthServiceProvider.php
│
├── Repositories/                      ◄─── Repository Pattern
│   ├── Contracts/                     ◄─── Interfaces
│   │   ├── BoardRepositoryInterface.php
│   │   ├── CardRepositoryInterface.php
│   │   └── ColumnRepositoryInterface.php
│   └── Eloquent/                      ◄─── Implémentations
│       ├── BoardRepository.php
│       ├── CardRepository.php
│       └── ColumnRepository.php
│
└── Services/                          ◄─── Services
    ├── Model/                         ◄─── Services métier par Model
    │   ├── BoardService.php
    │   ├── CardService.php
    │   └── ColumnService.php
    └── Notification/
        └── NotificationService.php


routes/
├── api.php                            ◄─── Routes API
├── auth.php                           ◄─── Routes authentification
├── channels.php                       ◄─── Canaux Broadcasting
├── console.php                        ◄─── Scheduler
└── web.php                            ◄─── Routes Web


config/
├── activitylog.php                    ◄─── Audit Log
├── health.php                         ◄─── Health Checks
├── permission.php                     ◄─── Spatie Permission
└── ...


database/
├── factories/
├── migrations/
└── seeders/
    └── RolesAndPermissionsSeeder.php


tests/
└── Feature/
    └── Api/
        ├── ApiTestCase.php
        ├── BoardTest.php
        ├── CardTest.php
        └── ColumnTest.php
```
