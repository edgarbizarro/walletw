
# API de Carteira Digital

Uma API RESTful para gerenciamento de carteiras financeiras com funcionalidades de depósito, transferência e estorno de transações.

## Funcionalidades Principais

-   Cadastro e autenticação de usuários
    
-   Gerenciamento de saldo da carteira
    
-   Depósito de fundos
    
-   Transferência para outros usuários
    
-   Estorno de transações
    
-   Histórico completo de transações
    
-   Processamento assíncrono para transferências grandes
    
-   Logs detalhados e monitoramento
    

## Requisitos Técnicos

-   PHP 8.0+
    
-   Composer
    
-   MySQL 5.7+
    
-   Redis (opcional, para filas)
    
-   Laravel Sail (Docker)
    

## Instalação Local

Com Laravel Sail:

1.  Clone o repositório:
    
    ```
    git clone https://github.com/edgarbizarro/walletw.git
    cd wallet-api
    
    ```
    
2.  Instale as dependências usando o Sail:
    
    ```
    ./vendor/bin/sail composer install
    
    ```
    
3.  Copie o arquivo de ambiente:
    
    ```
    cp .env.example .env
    
    ```
    
4.  Gere a chave da aplicação:
    
    ```
    ./vendor/bin/sail artisan key:generate
    
    ```
    
5.  Configure o banco de dados no arquivo `.env`:
    
    ```
    DB_CONNECTION=mysql
    DB_HOST=mysql # Nome do serviço no docker-compose.yml
    DB_PORT=3306
    DB_DATABASE=wallet
    DB_USERNAME=wallet
    DB_PASSWORD=secret
    
    ```
    
6.  Execute as migrações:
    
    ```
    ./vendor/bin/sail artisan migrate
    
    ```
    
7.  Inicie o ambiente de desenvolvimento com Sail:
    
    ```
    ./vendor/bin/sail up -d
    
    ```
    
8.  Instale as dependências frontend (se necessário):
    
    ```
    ./vendor/bin/sail npm install
    ./vendor/bin/sail npm run dev
    
    ```
    
9.  Acesse a aplicação em http://localhost
    

### Executando com Docker (Laravel Sail)

1.  Inicie o ambiente com Sail:
    
    ```
    ./vendor/bin/sail up -d
    
    ```
    
2.  Execute as migrações:
    
    ```
    ./vendor/bin/sail artisan migrate
    
    ```
    
3.  Acesse a API em http://localhost
    

## Testando a Aplicação

Execute os testes com Sail:

```
./vendor/bin/sail artisan test

```

## Documentação da API

1.  Gere a documentação da API:
    
    ```
    ./vendor/bin/sail artisan apidoc:generate
    
    ```
    
2.  Acesse a documentação em `/docs` após iniciar o servidor.
    

## Monitoramento

Acesse o Telescope em `/telescope` para monitoramento de requisições e depuração.

## Endpoints da API

### Autenticação

-   `POST /api/register` - Cadastra um novo usuário
    
-   `POST /api/login` - Autentica um usuário
    
-   `POST /api/logout` - Encerra a sessão (requer autenticação)
    

### Carteira (requer autenticação)

-   `POST /api/wallet/deposit` - Realiza um depósito
    
-   `POST /api/wallet/transfer` - Transfere fundos para outro usuário
    
-   `POST /api/wallet/reverse/{transaction}` - Estorna uma transação
    
-   `GET /api/wallet/balance` - Consulta o saldo atual
    
-   `GET /api/wallet/transactions` - Lista o histórico de transações
    

## Exemplo de Uso

1.  Registre um novo usuário:
    
    ```
    curl -X POST http://localhost/api/register \
      -H "Content-Type: application/json" \
      -d '{
        "name": "João Silva",
        "email": "joao@example.com",
        "password": "senhaSegura123",
        "password_confirmation": "senhaSegura123",
        "document": "12345678901",
        "type": "individual"
      }'
    
    ```
    
2.  Autentique-se:
    
    ```
    curl -X POST http://localhost/api/login \
      -H "Content-Type: application/json" \
      -d '{
        "email": "joao@example.com",
        "password": "senhaSegura123"
      }'
    
    ```
    
3.  Faça um depósito:
    
    ```
    curl -X POST http://localhost/api/wallet/deposit \
      -H "Authorization: Bearer <seu_token>" \
      -H "Content-Type: application/json" \
      -d '{
        "amount": 100.50,
        "description": "Salário"
      }'
    
    ```
    
4.  Consulte o saldo:
    
    ```
    curl -X GET http://localhost/api/wallet/balance \
      -H "Authorization: Bearer <seu_token>"
    
    ```
    

## Logs

Os logs são armazenados em formato JSON em `storage/logs/laravel.log` e incluem:

-   Todas as requisições à API
    
-   Erros e exceções
    
-   Eventos importantes do sistema
    

## Observabilidade

O Laravel Telescope fornece um painel completo para monitoramento em `/telescope` com:

-   Requisições HTTP
    
-   Consultas ao banco de dados
    
-   Jobs em fila
    
-   Eventos
    
-   Exceções
    
-   Logs
    

## Considerações de Segurança

-   Todas as rotas de carteira requerem autenticação via token Bearer
    
-   Senhas são armazenadas usando bcrypt
    
-   Transações são validadas para evitar saldo negativo
    
-   Transferências grandes são processadas assincronamente
    
-   Todas as operações são registradas em log para auditoria
