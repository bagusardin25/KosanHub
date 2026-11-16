<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>KosanHub</title>
    <style>
        :root { --bg: #f6f7f9; --card: #fff; --text: #1d2433; --muted: #667085; --line: #e4e7ec; --accent: #0f766e; --warn: #b42318; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: system-ui, -apple-system, "Segoe UI", sans-serif; background: var(--bg); color: var(--text); }
        header { background: var(--accent); color: #fff; padding: 20px 16px; }
        header h1 { margin: 0; font-size: 22px; }
        header p { margin: 4px 0 0; opacity: .85; font-size: 14px; }
        main { max-width: 1000px; margin: 0 auto; padding: 20px 16px 40px; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; }
        .card { background: var(--card); border: 1px solid var(--line); border-radius: 10px; padding: 16px; }
        .card .label { color: var(--muted); font-size: 13px; }
        .card .value { font-size: 28px; font-weight: 600; margin-top: 4px; }
        h2 { font-size: 17px; margin: 28px 0 10px; }
        .table-wrap { overflow-x: auto; background: var(--card); border: 1px solid var(--line); border-radius: 10px; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th, td { text-align: left; padding: 10px 12px; border-bottom: 1px solid var(--line); white-space: nowrap; }
        th { color: var(--muted); font-weight: 500; background: #fafbfc; }
        tr:last-child td { border-bottom: none; }
        .num { text-align: right; font-variant-numeric: tabular-nums; }
        .warn { color: var(--warn); font-weight: 600; }
        .empty { padding: 16px; color: var(--muted); }
        code { background: #eef2f6; padding: 2px 6px; border-radius: 4px; font-size: 13px; }
        ul.endpoints { background: var(--card); border: 1px solid var(--line); border-radius: 10px; padding: 14px 14px 14px 32px; margin: 0; line-height: 1.9; font-size: 14px; }
    </style>
</head>
<body>
<header>
    <h1>KosanHub</h1>
    <p>Pengelolaan kos-kosan &amp; tagihan bulanan · versi {{ $version }}</p>
</header>
<main>
    <section class="stats">
        <div class="card"><div class="label">Properti</div><div class="value">{{ $stat['properti'] }}</div></div>
        <div class="card"><div class="label">Kamar</div><div class="value">{{ $stat['kamar'] }}</div></div>
        <div class="card"><div class="label">Kamar terisi</div><div class="value">{{ $stat['kamar_terisi'] }}</div></div>
        <div class="card"><div class="label">Tagihan belum lunas</div><div class="value">{{ $stat['tagihan_belum_lunas'] }}</div></div>
    </section>

    <h2>Daftar tunggakan</h2>
    <div class="table-wrap">
        @if ($tunggakan->isEmpty())
            <div class="empty">Tidak ada tunggakan.</div>
        @else
            <table>
                <thead>
                <tr>
                    <th>Penyewa</th><th>Kamar</th><th>Periode</th><th>Jatuh tempo</th>
                    <th class="num">Hari telat</th><th class="num">Denda</th><th class="num">Total hari ini</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($tunggakan as $t)
                    <tr>
                        <td>{{ $t['penyewa'] }}</td>
                        <td>{{ $t['properti'] }} · {{ $t['kamar'] }}</td>
                        <td>{{ $t['periode'] }}</td>
                        <td>{{ $t['jatuh_tempo'] }}</td>
                        <td class="num warn">{{ $t['hari_telat'] }}</td>
                        <td class="num">{{ $t['persen_denda'] }}% · Rp{{ number_format($t['denda_hari_ini'], 0, ',', '.') }}</td>
                        <td class="num">Rp{{ number_format($t['total_hari_ini'], 0, ',', '.') }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <h2>Proses pembuatan tagihan terakhir (container worker)</h2>
    <div class="table-wrap">
        @if ($runTerakhir->isEmpty())
            <div class="empty">Belum ada proses. Picu lewat <code>POST /api/invoice-runs</code>.</div>
        @else
            <table>
                <thead>
                <tr><th>ID</th><th>Periode</th><th>Status</th><th class="num">Dibuat</th><th class="num">Dilewati</th><th>Selesai</th></tr>
                </thead>
                <tbody>
                @foreach ($runTerakhir as $run)
                    <tr>
                        <td>#{{ $run->id }}</td>
                        <td>{{ $run->periode }}</td>
                        <td>{{ $run->status }}</td>
                        <td class="num">{{ $run->jumlah_dibuat }}</td>
                        <td class="num">{{ $run->jumlah_dilewati }}</td>
                        <td>{{ $run->selesai_pada?->format('Y-m-d H:i:s') ?? '-' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <h2>Endpoint API</h2>
    <ul class="endpoints">
        <li><code>GET /health</code> status aplikasi &amp; koneksi database</li>
        <li><code>/api/properties</code>, <code>/api/rooms</code>, <code>/api/tenants</code> CRUD</li>
        <li><code>/api/contracts</code> kontrak sewa · <code>POST /api/contracts/{id}/end</code></li>
        <li><code>POST /api/invoice-runs</code> picu pembuatan tagihan · <code>GET /api/invoice-runs/{id}</code></li>
        <li><code>GET /api/invoices</code> · <code>POST /api/invoices/{id}/payments</code></li>
        <li><code>GET /api/arrears</code> daftar tunggakan</li>
    </ul>
</main>
</body>
</html>
