
  

# 💳Wallet API

  

Uma API RESTful para gerenciar carteiras digitais, incluindo funcionalidades de depósito, transferência e reversão de transações.

  

## 📦 Funcionalidades

  
✅ Registro e autenticação de usuários

✅ Gerenciamento do saldo da carteira

✅ Depositar fundos na carteira

✅ Transferir fundos entre usuários

✅ Reversão de transações

✅ Histórico completo de transações

✅ Processamento assíncrono para transferências de grande valor

✅ Registros detalhados e observabilidade com o Laravel Telescope

  

## 🧰 Tecnologias
  

- PHP 8.2 - 8.4
- Laravel 12
- MySQL 5.7+
- Laravel Sail (Docker)
- Tailwind

  

## 🛠️ Instruções para Execução

  

### 🛟 Pré-requisitos

- Docker

- Composer

  

### 🔧Instalação

``` 
git clone  https://github.com/edgarbizarro/walletw.git
``` 
``` 
cd walletw 
```

### 🔧Instalar dependências

```
composer install
```

> Entretanto, em vez de digitar repetidamente vendor/bin/sail para executar comandos do Sail, você pode configurar um alias de shell que permita executar os comandos do Sail mais facilmente:
`` alias sail='sh $([ -f sail ] && echo sail || echo vendor/bin/sail)' ``

  

### 🔧 Criar arquivo .env
``` 
cp .env.example .env
``` 


### 🔧 Gerar chave de aplicação
```
./vendor/bin/sail artisan key:generate
```
  

### 🔧 Configure DB in .env file
```
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=walletw
DB_USERNAME=sail
DB_PASSWORD=password
```

### 🔧 Inicie a aplicação
```
./vendor/bin/sail up -d
```
   
### 🔧 Executar as migrations
```
./vendor/bin/sail artisan migrate
```
A aplicação estará disponível em: http://localhost
 
### Opcional: Compilar o front-end
```
./vendor/bin/sail npm install
```
```
./vendor/bin/sail npm run dev
```


### 🧪 Testes
```
./vendor/bin/sail artisan test
```

  

## 📘 Documentação da API

  

Gerar e acessar documentação da API:
```
./vendor/bin/sail artisan scramble:export
```

Em seguida, abra no seu navegador http://localhost/docs/api 
>A documentação já esta gerada por padrão 😉

  

## 🔐 Autenticação

| Método | Endpoint | Descrição |
|--|--|--|
| POST  | `/api/register` | Registrar novo usuário | 


```curl
curl --location 'http://localhost/api/register' \
--header 'Accept: application/json' \
--header 'Content-Type: application/json' \
--data-raw '{
  "name": "John Doe",
  "email": "john@doe.com",
  "password": "123123123",
  "document": "12312312321",
  "type": "individual",
  "password_confirmation": "123123123"
}'
```

| Método | Endpoint | Descrição |
|--|--|--|
| POST  | `/api/login` | Autenticar usuário 
 
```curl
curl --location 'http://localhost/api/login' \
--header 'Accept: application/json' \
--header 'Content-Type: application/json' \
--data-raw '{
  "email": "john@doe.com",
  "password": "123123123",
}'
```

| Método | Endpoint | Descrição |
|--|--|--|
| POST  | `/api/logout` | Deslogar usuário | 
 
```curl
curl --location --request POST 'http://localhost/api/logout' \
--header 'Accept: application/json' \
--header 'Content-Type: application/json' \
--header 'Authorization: Bearer {seu_token}'
```
  

## 💰 Wallet (Requer autenticação)  

| Método | Endpoint | Descrição |
|--|--| -- |
| POST  | `/api/wallet/deposit` | Depositar fundos |

```curl
curl --location 'http://localhost/api/wallet/deposit' \
--header 'Accept: application/json' \
--header 'Content-Type: application/json' \
--header 'Authorization: Bearer {seu_token}\
--data '{
  "amount": 1.09,
  "description": "string"
}'
```

| Método | Endpoint | Descrição |
|--|--| -- |
| POST  | `/api/wallet/transfer` | Transferir para outro usuário |
```curl
curl --location 'http://localhost/api/wallet/transfer' \
--header 'Accept: application/json' \
--header 'Content-Type: application/json' \
--header 'Authorization: Bearer {seu_token} \
--data '{
  "amount": 0.10,
  "recipient_id": 2,
  "description": "string"
}'
```


| Método | Endpoint | Descrição |
|--|--| -- |
| POST  | `/api/wallet/reverse/{id}` | Reverter uma transação |
```curl
curl --location --request POST 'http://localhost/api/wallet/reverse/{transaction_id}' \
--header 'Accept: application/json' \
--header 'Content-Type: application/json' \
--header 'Authorization: Bearer {seu_token}'
```

| Método | Endpoint | Descrição |
|--|--| -- |
| GET | `/api/wallet/balance` | Recupere o saldo atual da carteira |
```curl
curl --location 'http://localhost/api/wallet/balance' \
--header 'Accept: application/json' \
--header 'Authorization: Bearer {seu_token}'
```

| Método | Endpoint | Descrição |
|--|--| -- |
| GET | `/api/wallet/transactions` | Verificar saldo da carteira |
```curl
curl --location 'http://localhost/api/wallet/transactions' \
--header 'Accept: application/json' \
--header 'Authorization: Bearer {seu_token}'
```

## 📚 Documentação

-   Documentação interativa via  `/api/docs`    
-   Especificação OpenAPI 3.0 gerada automaticamente    
-   Exemplos de requisições/respostas

## 📊 Monitoramento

O Laravel Telescope está disponível em `/telescope` e fornece: 

- Logs de requisições
- Consultas ao banco de dados
- Eventos, exceções e logs

## ⚙️ Arquitetura

-   **Padrão em Camadas**  (Controller → Service → Repository)
-   **Transações Atômicas**  (Garantia de consistência)    
-   **UUID**  para identificação segura de transações
  

## 🛡️ Segurança

-   Autenticação via token (Sanctum)    
-   Bloqueio de operações com saldo negativo    
-   Validação rigorosa em todas as operações    
-   Logs estruturados para auditoria

  

---

>"Uma solução robusta para gestão financeira pessoal, construída com as melhores práticas de desenvolvimento moderno."
  

Desenvolvido por Edgar Bizarro
