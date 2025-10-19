<?php
// =====================================================
// PARTE PHP — PROCESSA PIX QUANDO O FORM É ENVIADO
// =====================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $modo = 'producao'; // ou 'sandbox'
    $client_id = "SEU_CLIENT_ID";
    $client_secret = "SEU_CLIENT_SECRET";
    $chave_pix = "SUA_CHAVE_PIX";
    $certificado = __DIR__ . "/producao.pem";

    $nome = trim($_POST['nome']);
    $telefone = trim($_POST['telefone']);

    // Gera token
    $url_token = $modo === 'sandbox'
        ? "https://api-sandbox.efi.com.br/oauth/token"
        : "https://api.efi.com.br/oauth/token";

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url_token,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_USERPWD => "$client_id:$client_secret",
        CURLOPT_POSTFIELDS => "grant_type=client_credentials",
        CURLOPT_SSLCERT => $certificado,
        CURLOPT_SSLKEY => $certificado
    ]);
    $res_token = curl_exec($curl);
    $token = json_decode($res_token)->access_token ?? null;
    curl_close($curl);

    if (!$token) {
        die("<p style='color:red;text-align:center;'>Erro ao obter token Pix.</p>");
    }

    // Cria cobrança
    $url_pix = $modo === 'sandbox'
        ? "https://api-sandbox.efi.com.br/v2/cob"
        : "https://api.efi.com.br/v2/cob";

    $payload = [
        "calendario" => ["expiracao" => 3600],
        "valor" => ["original" => "85.00"],
        "chave" => $chave_pix,
        "solicitacaoPagador" => "Bolão - $nome"
    ];

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url_pix,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ["Authorization: Bearer $token", "Content-Type: application/json"],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_SSLCERT => $certificado,
        CURLOPT_SSLKEY => $certificado
    ]);
    $res_pix = json_decode(curl_exec($curl), true);
    curl_close($curl);

    $qr_code = $res_pix['imagemQrcode'] ?? '';
    $copia_cola = $res_pix['pixCopiaECola'] ?? '';

    echo "<!DOCTYPE html><html lang='pt-BR'><head><meta charset='UTF-8'><meta name='viewport' content='width=device-width, initial-scale=1.0'><title>Pagamento Pix</title>
    <style>body{display:flex;justify-content:center;align-items:center;min-height:100vh;background:#f5f5f5;font-family:Arial;text-align:center}
    .box{background:#fff;padding:30px;border-radius:12px;box-shadow:0 5px 20px rgba(0,0,0,0.1);}
    img{max-width:250px;margin-top:10px}</style></head><body>
    <div class='box'>
        <h2>Escaneie o QR Code Pix</h2>
        <img src='$qr_code' alt='QR Code'>
        <p><strong>Copia e cola:</strong><br>$copia_cola</p>
    </div></body></html>";
    exit;
}
?>

<!-- =====================================================
     PARTE HTML + JS — FORMULÁRIO INICIAL
===================================================== -->
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bolão - Quero Participar</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', sans-serif; }
    body { display:flex;justify-content:center;align-items:center;min-height:100vh;background:#f5f5f5;padding:20px; }
    .container { background:#fff;padding:30px;border-radius:12px;box-shadow:0 8px 20px rgba(0,0,0,0.1);max-width:350px;width:100%;text-align:center; }
    h2 { color:#005A9C;margin-bottom:25px; }
    form { display:flex;flex-direction:column;gap:15px; }
    input { padding:12px;border-radius:8px;border:1px solid #ccc;font-size:16px;width:100%; }
    button { padding:15px;background:#005A9C;color:#fff;font-weight:bold;border:none;border-radius:8px;cursor:pointer; }
    button:hover { background:#004080; }
    @media (max-width:400px){ .container{padding:20px;} input,button{font-size:14px;} }
  </style>
</head>
<body>
  <div class="container">
    <h2>Entre para o bolão!</h2>
    <form method="POST">
      <input type="text" name="nome" placeholder="Seu nome completo" required>
      <input type="tel" name="telefone" placeholder="Seu WhatsApp (DDD + número)" required>
      <button type="submit">Quero Participar</button>
    </form>
  </div>
</body>
</html>
