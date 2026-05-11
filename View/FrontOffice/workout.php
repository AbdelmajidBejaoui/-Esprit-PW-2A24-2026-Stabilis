<?php
require_once __DIR__ . '/partials/auth.php';
requireLogin();
require_once __DIR__ . '/../../Controller/EntrainementC.php';
$eC = new EntrainementC();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$entrainement = $eC->getById($id);
if (!$entrainement) { header('Location: programme.php'); exit; }
$etapes = $eC->getEtapes($id);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Workout – <?= htmlspecialchars($entrainement->getNom()) ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<link href="https://fonts.googleapis.com/css?family=Poppins:300,400,600,700,800&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Poppins',sans-serif;background:#0f1117;color:#fff;overflow:hidden;height:100vh;}
#app{height:100vh;display:flex;flex-direction:column;}
.wk-header{padding:16px 24px;display:flex;justify-content:space-between;align-items:center;background:rgba(0,0,0,.4);backdrop-filter:blur(10px);z-index:10;}
.wk-progress{height:4px;background:rgba(255,255,255,.1);}
.wk-progress-fill{height:100%;background:linear-gradient(90deg,#82ae46,#43e97b);transition:width .5s ease;}
.wk-stage{flex:1;display:flex;align-items:center;justify-content:center;position:relative;overflow:hidden;}
.blob{position:absolute;border-radius:50%;filter:blur(80px);opacity:.2;animation:blobMove 8s ease-in-out infinite;}
.blob1{width:500px;height:500px;background:#82ae46;top:-100px;left:-100px;}
.blob2{width:400px;height:400px;background:#4facfe;bottom:-80px;right:-80px;animation-delay:-3s;}
.blob3{width:300px;height:300px;background:#f093fb;top:40%;left:50%;animation-delay:-5s;}
@keyframes blobMove{0%,100%{transform:translate(0,0)scale(1);}50%{transform:translate(30px,-30px)scale(1.1);}}
.step-display{max-width:680px;width:100%;padding:0 24px;position:relative;z-index:5;text-align:center;}
.step-num{display:inline-flex;align-items:center;justify-content:center;width:56px;height:56px;background:linear-gradient(135deg,#82ae46,#43e97b);border-radius:50%;font-size:1.4rem;font-weight:800;margin-bottom:18px;box-shadow:0 0 30px rgba(130,174,70,.5);}
.step-title{font-size:1.8rem;font-weight:700;margin-bottom:12px;line-height:1.2;}
.step-desc{font-size:.95rem;color:rgba(255,255,255,.75);margin-bottom:16px;line-height:1.7;}
.step-conseil{display:inline-flex;align-items:center;gap:8px;background:rgba(255,193,7,.12);border:1px solid rgba(255,193,7,.3);color:#ffc107;padding:8px 18px;border-radius:12px;font-size:.85rem;margin-bottom:24px;}
.timer-wrap{position:relative;width:150px;height:150px;margin:0 auto 20px;}
.timer-svg{position:absolute;top:0;left:0;transform:rotate(-90deg);}
.timer-svg circle{transition:stroke-dashoffset .5s linear;stroke-linecap:round;}
.timer-digits{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);text-align:center;}
.timer-secs{font-size:2.5rem;font-weight:800;line-height:1;}
.timer-label{font-size:.7rem;color:rgba(255,255,255,.5);text-transform:uppercase;letter-spacing:1px;}
.wk-controls{display:flex;justify-content:center;gap:12px;}
.wk-btn{border:none;border-radius:50px;padding:11px 24px;font-family:'Poppins',sans-serif;font-size:.88rem;font-weight:600;cursor:pointer;transition:.2s;display:inline-flex;align-items:center;gap:7px;}
.wk-btn-main{background:linear-gradient(135deg,#82ae46,#43e97b);color:#fff;box-shadow:0 0 20px rgba(130,174,70,.4);}
.wk-btn-main:hover{transform:translateY(-2px);}
.wk-btn-sec{background:rgba(255,255,255,.1);color:#fff;}
.wk-footer{padding:14px 24px;display:flex;justify-content:center;gap:8px;background:rgba(0,0,0,.3);z-index:10;}
.step-dot{width:8px;height:8px;border-radius:50%;background:rgba(255,255,255,.2);transition:.3s;cursor:pointer;}
.step-dot.active{background:#82ae46;width:22px;border-radius:4px;}
.step-dot.done{background:rgba(130,174,70,.5);}
#finish{position:fixed;inset:0;background:#0f1117;z-index:100;display:none;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:40px;}
.confetti-piece{position:absolute;width:8px;height:8px;border-radius:2px;animation:fall 2.5s ease forwards;}
@keyframes fall{0%{transform:translateY(-60px)rotate(0deg);opacity:1;}100%{transform:translateY(100vh)rotate(720deg);opacity:0;}}
</style>
</head>
<body>
<?php if(empty($etapes)): ?>
<div style="display:flex;align-items:center;justify-content:center;height:100vh;flex-direction:column;text-align:center;padding:40px;">
    <i class="fas fa-exclamation-circle fa-4x mb-4" style="color:#ffc107;"></i>
    <h3>Aucune étape disponible</h3>
    <a href="detail.php?id=<?= $id ?>" style="background:#82ae46;color:#fff;padding:12px 28px;border-radius:30px;text-decoration:none;margin-top:16px;">Retour</a>
</div>
<?php else: ?>
<script>
const STEPS=<?= json_encode(array_values($etapes)) ?>;
const ENT_ID=<?= $id ?>;
const C=2*Math.PI*68;
</script>
<div id="app">
    <div class="wk-header">
        <div>
            <div style="font-size:.9rem;color:#82ae46;font-weight:700;">🏋️ FitTrack Workout</div>
            <div style="font-size:.8rem;color:rgba(255,255,255,.6);"><?= htmlspecialchars($entrainement->getNom()) ?></div>
        </div>
        <button class="wk-btn wk-btn-sec" onclick="location.href='log_seance.php?id=<?= $id ?>'"><i class="fas fa-times"></i> Terminer & Logger</button>
    </div>
    <div class="wk-progress"><div class="wk-progress-fill" id="prog" style="width:0%"></div></div>
    <div class="wk-stage">
        <div class="blob blob1"></div><div class="blob blob2"></div><div class="blob blob3"></div>
        <div class="step-display" id="step-display"></div>
    </div>
    <div class="wk-footer" id="dots"></div>
</div>
<div id="finish">
    <div style="font-size:5rem;margin-bottom:16px;">🏆</div>
    <h1 style="font-size:2.2rem;font-weight:800;margin-bottom:10px;">Entraînement terminé !</h1>
    <p style="color:rgba(255,255,255,.6);margin-bottom:28px;">Félicitations ! <?= count($etapes) ?> étapes complétées.</p>
    <div style="display:flex;gap:12px;flex-wrap:wrap;justify-content:center;">
        <a href="log_seance.php?id=<?= $id ?>" style="background:linear-gradient(135deg,#82ae46,#43e97b);color:#fff;padding:12px 28px;border-radius:30px;text-decoration:none;font-weight:600;">
            <i class="fas fa-save mr-2"></i>Enregistrer la séance
        </a>
        <a href="programme.php" style="background:rgba(255,255,255,.1);color:#fff;padding:12px 28px;border-radius:30px;text-decoration:none;">Mon programme</a>
    </div>
</div>
<script>
let cur=0,tLeft=0,timer=null,paused=false;
function render(i){
    const s=STEPS[i]; tLeft=s.duree_secondes; paused=false; clearInterval(timer);
    document.getElementById('prog').style.width=((i/STEPS.length)*100)+'%';
    document.querySelectorAll('.step-dot').forEach((d,j)=>d.className='step-dot'+(j===i?' active':j<i?' done':''));
    document.getElementById('step-display').innerHTML=`
        <div class="step-num">${i+1}</div>
        <div class="step-title">${esc(s.titre)}</div>
        <div class="step-desc">${esc(s.description)}</div>
        ${s.conseil?`<div class="step-conseil"><i class="fas fa-lightbulb"></i>${esc(s.conseil)}</div>`:''}
        <div class="timer-wrap">
            <svg class="timer-svg" width="150" height="150">
                <circle cx="75" cy="75" r="68" fill="none" stroke="rgba(255,255,255,.1)" stroke-width="6"/>
                <circle id="ring" cx="75" cy="75" r="68" fill="none" stroke="#82ae46" stroke-width="6" stroke-dasharray="${C}" stroke-dashoffset="0"/>
            </svg>
            <div class="timer-digits"><div class="timer-secs" id="tsecs">${fmt(tLeft)}</div><div class="timer-label">secondes</div></div>
        </div>
        <div class="wk-controls">
            <button class="wk-btn wk-btn-sec" onclick="prev()" ${i===0?'disabled':''}><i class="fas fa-step-backward"></i></button>
            <button class="wk-btn wk-btn-main" id="playbtn" onclick="togglePause()"><i class="fas fa-play"></i> Démarrer</button>
            <button class="wk-btn wk-btn-sec" onclick="next()">${i===STEPS.length-1?'<i class="fas fa-flag-checkered"></i> Fin':'<i class="fas fa-step-forward"></i> Passer'}</button>
        </div>`;
}
function startTimer(){
    const total=STEPS[cur].duree_secondes;
    timer=setInterval(()=>{
        if(paused)return;
        tLeft--;
        document.getElementById('tsecs').textContent=fmt(tLeft);
        const pct=tLeft/total;
        const c=document.getElementById('ring');
        if(c){c.style.strokeDashoffset=C*(1-tLeft/total);c.setAttribute('stroke',pct>.5?'#82ae46':pct>.25?'#ffc107':'#f5576c');}
        if(tLeft<=0){clearInterval(timer);timer=null;if(cur<STEPS.length-1)setTimeout(()=>{cur++;render(cur);},600);else setTimeout(showFinish,600);}
    },1000);
}
function togglePause(){
    const btn=document.getElementById('playbtn');
    if(!timer||paused){paused=false;if(!timer){clearInterval(timer);startTimer();}btn.innerHTML='<i class="fas fa-pause"></i> Pause';}
    else{paused=true;btn.innerHTML='<i class="fas fa-play"></i> Reprendre';}
}
function next(){clearInterval(timer);timer=null;if(cur<STEPS.length-1){cur++;render(cur);}else showFinish();}
function prev(){clearInterval(timer);timer=null;if(cur>0){cur--;render(cur);}}
function showFinish(){document.getElementById('prog').style.width='100%';const f=document.getElementById('finish');f.style.display='flex';['#82ae46','#ffc107','#f093fb','#4facfe','#f5576c'].forEach(c=>{for(let i=0;i<12;i++){const el=document.createElement('div');el.className='confetti-piece';el.style.cssText=`left:${Math.random()*100}%;top:0;background:${c};animation-delay:${Math.random()*1.5}s;`;f.appendChild(el);}});}
function fmt(s){return s>=60?Math.floor(s/60)+'m '+(s%60<10?'0':'')+s%60+'s':s+'s';}
function esc(t){const d=document.createElement('div');d.textContent=t;return d.innerHTML;}
// Init dots
const dotsEl=document.getElementById('dots');
STEPS.forEach((_,i)=>{const d=document.createElement('div');d.className='step-dot';d.onclick=()=>{clearInterval(timer);timer=null;cur=i;render(i);};dotsEl.appendChild(d);});
document.addEventListener('keydown',e=>{if(e.code==='Space'){e.preventDefault();togglePause();}if(e.code==='ArrowRight')next();if(e.code==='ArrowLeft')prev();});
render(0);
</script>
<?php endif; ?>
</body></html>
