# De ERD
  ``` mermaid
  erDiagram
    direction LR

    BOOK {
        string ISBN PK "Uniek boeknummer"
        string title
        int publicationYear
    }

    COPY {
        string copyId PK
        string bookId FK
        string status "beschikbaar / uitgeleend / beschadigd"
    }

    AUTHOR {
        string authorId PK
        string name
    }

    BOOK_AUTHOR {
        string bookId PK, FK
        string authorId PK, FK
    }

    CATEGORY {
        string categoryId PK
        string name
    }

    MEMBER {
        string memberId PK
        string name
        string email
    }

    STAFF {
        string staffId PK
        string name
        string role
    }

    ACCOUNT {
        string accountId PK
        string username
        string passwordHash
        string role "member / staff"
    }

    LOAN {
        string loanId PK
        string copyId FK
        string memberId FK
        string staffId FK
        date loanDate
        date returnDate
    }

    RESERVATION {
        string reservationId PK
        date dateRequested
        string status "open / fulfilled / canceled"
    }

    FINE {
        string fineId PK
        float amount
        string reason
        date issuedDate
        string memberId FK
        string loanId FK
    }

    %% RELATIONSHIPS
    BOOK ||--o{ COPY : has
    BOOK ||--o{ BOOK_AUTHOR : includes_author
    AUTHOR ||--o{ BOOK_AUTHOR : contributes_to

    BOOK ||--|| CATEGORY : belongs_to

    COPY ||--o{ LOAN : loaned_as
    MEMBER ||--o{ LOAN : makes
    STAFF ||--o{ LOAN : processes

    MEMBER ||--o{ RESERVATION : places
    BOOK ||--o{ RESERVATION : reserved_for

    MEMBER ||--o{ FINE : receives
    LOAN ||--o{ FINE : results_in

    ACCOUNT ||--|| MEMBER : owns
    ACCOUNT ||--|| STAFF : owns
```

# De REST API 
``` mermaid
    sequenceDiagram
    participant Client
    participant API
    participant DB

    Client->>API: GET /books
    API->>DB: SELECT * FROM books
    DB-->>API: List of books
    API-->>Client: 200 OK + books

    Client->>API: POST /loans {bookId, memberId}
    API->>DB: Check availability of bookId
    DB-->>API: Book available
    API->>DB: INSERT INTO loans (bookId, memberId)
    DB-->>API: Loan created
    API-->>Client: 201 Created

    Client->>API: GET /loans
    API->>DB: SELECT * FROM loans WHERE memberId = ?
    DB-->>API: List of current loans
    API-->>Client: 200 OK + loans

    Client->>API: POST /reservations {bookId, memberId}
    API->>DB: Check availability
    DB-->>API: Book not available
    API->>DB: INSERT INTO reservations (bookId, memberId)
    DB-->>API: Reservation created
    API-->>Client: 201 Created

    Client->>API: POST /login {username, password}
    API->>DB: SELECT * FROM accounts WHERE username = ?
    DB-->>API: User record
    API-->>Client: 200 OK + JWT token

    Client->>API: GET /fines
    API->>DB: SELECT * FROM fines WHERE memberId = ?
    DB-->>API: List of fines
    API-->>Client: 200 OK + fines
```

# De USE CASE
``` mermaid
 flowchart LR
  subgraph Gebruikers
    Gebruiker["👤 Gebruiker"]
    Medewerker["👩‍💼 Bibliotheekmedewerker"]
  end

  subgraph "Use-cases"
    Zoek(Zoek boeken)
    Bekijk(Bekijk boekdetails)
    Reserveer(Reserveer boek)
    Leen(Leen boek)
    Retourneer(Retourneer boek)
    Login(Log in)
    Registreer(Registreer account)
    BeheerBoeken(Beheer boeken)
    BeheerLeden(Beheer leden)
    BeheerReserveringen(Beheer reserveringen)
    VerwerkBoetes(Verwerk boetes)
  end

  Gebruiker --> Zoek
  Gebruiker --> Bekijk
  Gebruiker --> Reserveer
  Gebruiker --> Leen
  Gebruiker --> Retourneer
  Gebruiker --> Login
  Gebruiker --> Registreer

  Medewerker --> Zoek
  Medewerker --> Bekijk
  Medewerker --> Leen
  Medewerker --> Retourneer
  Medewerker --> BeheerBoeken
  Medewerker --> BeheerLeden
  Medewerker --> BeheerReserveringen
  Medewerker --> VerwerkBoetes
```
# De flowchart
``` mermaid
flowchart TD
    Start([Start]) --> Login{Ingelogd?}
    Login -- Ja --> ZoekBoek[📚 Zoek naar een boek]
    Login -- Nee --> Inloggen[🔐 Log in] --> ZoekBoek

    ZoekBoek --> BoekGevonden{Boek gevonden?}
    BoekGevonden -- Nee --> ZoekOpnieuw[🔁 Zoek opnieuw] --> ZoekBoek
    BoekGevonden -- Ja --> Beschikbaar{Boek beschikbaar?}

    Beschikbaar -- Ja --> LeenBoek[✅ Leen het boek]
    Beschikbaar -- Nee --> ReserveerBoek[🕓 Reserveer het boek]

    LeenBoek --> Beëindig[🏁 Klaar]
    ReserveerBoek --> Beëindig
```

#IDE omgeving diagram
```mermaid
%% Gereserveerd voor IDE diagram
```

# De REST API (Resource Map)
```mermaid
graph LR
    API[Bibliotheek API]
    
    %% Catalogus
    API -->|/catalogus| Cat[Catalogus]
    Cat -->|GET /boeken| GetBoeken[Zoek Boeken]
    Cat -->|POST /boeken| AddBoek[Boek Toevoegen]
    Cat -->|GET /boeken/:id| BoekDetail[Boek Details]
    
    %% Transacties
    API -->|/transacties| Trans[Transacties]
    Trans -->|POST /lenen| Leen[Boek Lenen]
    Trans -->|POST /retourneren| Retour[Boek Retourneren]
    Trans -->|POST /reserveren| Reserveer[Boek Reserveren]
    
    %% Account
    API -->|/account| Acc[Account]
    Acc -->|GET /profiel| Profiel[Mijn Gegevens]
    Acc -->|GET /historie| Hist[Leen Historie]
    Acc -->|GET /boetes| Boetes[Openstaande Boetes]

    style API fill:#f96,stroke:#333,stroke-width:2px,color:white
    style Cat fill:#ff9,stroke:#333
    style Trans fill:#9cf,stroke:#333
    style Acc fill:#c9f,stroke:#333
```
