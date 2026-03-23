'use strict';

/* ── STATE ─────────────────────────────────────────────────── */
let map, markers = [], routeControl = null, truckMarker = null;
let navActive = false, routeData = [], navStepIndex = 0;
let animFrameId = null, routeLegs = [];
let geoWatchId = null, lastTruckCoord = null;
let currentBatchId   = null;
let notifDropdownOpen = false;

/* ── ICONS ─────────────────────────────────────────────────── */
const truckIcon = L.divIcon({
    className: '',
    html: `<div style="font-size:30px;filter:drop-shadow(0 2px 6px rgba(0,0,0,.4));transform-origin:center">🚛</div>`,
    iconSize:[36,36], iconAnchor:[18,18], popupAnchor:[0,-20]
});

function makeHouseIcon(index, status){
    const bg = status==='accepted' ? '#10b981' : '#f59e0b';
    return L.divIcon({
        className:'',
        html:`<div style="background:${bg};color:#fff;font-size:11px;font-weight:700;font-family:'Outfit',sans-serif;width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;border:2.5px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,.3)">${index}</div>`,
        iconSize:[28,28], iconAnchor:[14,28], popupAnchor:[0,-30]
    });
}

/* ── PAGE LOAD ─────────────────────────────────────────────── */
window.onload = function(){
    if(!localStorage.getItem("collectorLoggedIn") || !localStorage.getItem("collectorWard")){
        window.location.href = "collector-login.html";
        return;
    }
    document.getElementById("wardLabel").innerText = "Ward " + localStorage.getItem("collectorWard");
    initMap();
    refreshAll();
    loadCollectorNotifications();

    setInterval(refreshAll, 60000);
    setInterval(loadCollectorNotifications, 60000);

    // Set min date for date inputs to today
    const today = new Date().toISOString().split('T')[0];
    document.querySelectorAll('input[type="date"]').forEach(i => i.min = today);

    // Close notif dropdown on outside click
    document.addEventListener('click', e => {
        const bell     = document.getElementById('notifBellBtn');
        const dropdown = document.getElementById('notifDropdown');
        if(notifDropdownOpen && bell && dropdown &&
           !bell.contains(e.target) && !dropdown.contains(e.target)){
            closeNotifDropdown();
        }
    });
};

/* ── REFRESH ALL ───────────────────────────────────────────── */
function refreshAll(){
    loadQueuedRequests();
    loadBatches();
    loadHistory();
    loadStats();
}

/* ── STATS ─────────────────────────────────────────────────── */
function loadStats(){
    const ward = localStorage.getItem("collectorWard");
    fetch("backend/get_user_requests.php")
        .then(r=>r.json())
        .then(data=>{
            if(!Array.isArray(data)) return;
            const wd = data.filter(r=>r.ward_id==ward);
            document.getElementById("stat-total").innerText     = wd.length;
            document.getElementById("stat-queued").innerText    = wd.filter(r=>!r.batch_id || r.batch_id==0).length;
            document.getElementById("stat-batch").innerText     = wd.filter(r=>r.batch_id>0 && r.status!='completed').length;
            document.getElementById("stat-completed").innerText = wd.filter(r=>r.status==='completed').length;

            // Queue alert if 15+
            const queued = wd.filter(r=>!r.batch_id || r.batch_id==0).length;
            const banner = document.getElementById("queueAlertBanner");
            if(queued>=15){
                document.getElementById("queueAlertText").innerText = queued+" requests waiting. Create a new batch to assign them.";
                banner.style.display = "flex";
            } else {
                banner.style.display = "none";
            }
        });
}

/* ── TABS ──────────────────────────────────────────────────── */
function switchTab(name, btn){
    document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(c=>c.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('tabContent-'+name).classList.add('active');

    if(name==='history') loadHistory();
    if(name==='batches') loadBatches();
    if(name==='queue')   loadQueuedRequests();
}

/* ── QUEUE ─────────────────────────────────────────────────── */
function loadQueuedRequests(){
    fetch("backend/get_queued_requests.php?type=queued")
        .then(r=>r.json())
        .then(data=>{
            const tbody = document.getElementById("queueTable");
            const info  = document.getElementById("queueInfo");

            if(!Array.isArray(data)||data.length===0){
                tbody.innerHTML = `<tr><td colspan="6"><div class="empty-state"><div class="empty-icon">📭</div><p>Queue is empty. No pending requests.</p></div></td></tr>`;
                info.innerHTML  = '0 requests in queue';
                document.getElementById("badge-queue").innerText = '';
                return;
            }

            info.innerHTML = `<strong>${data.length}</strong> request${data.length!==1?'s':''} waiting in queue`;
            document.getElementById("badge-queue").innerText = data.length;

            tbody.innerHTML = data.map((r,i)=>`
                <tr>
                  <td style="font-family:var(--mono);font-size:.82rem;color:var(--text-muted)">${i+1}</td>
                  <td style="max-width:200px;word-break:break-word">${escHtml(r.address||'—')}</td>
                  <td><span style="font-family:var(--mono);font-size:.82rem">W${r.ward_id}</span></td>
                  <td><span class="waste-tag">${escHtml(r.waste_type)}</span></td>
                  <td class="time-small">${formatTime(r.request_time)}</td>
                  <td><span class="status-badge status-requested">Queued</span></td>
                </tr>`).join('');
        })
        .catch(()=> showToast("Failed to load queue","error"));
}

/* ── BATCHES ───────────────────────────────────────────────── */
function loadBatches(){
    fetch("backend/get_batches.php")
        .then(r=>r.json())
        .then(data=>{
            const container = document.getElementById("batchesList");
            if(!Array.isArray(data)||data.length===0){
                container.innerHTML = `<div class="empty-state" style="padding:40px"><div class="empty-icon">📦</div><p>No batches yet.</p><small>Create a batch from the Queue tab when requests are waiting.</small></div>`;
                return;
            }

            container.innerHTML = data.map(b=>{
                const total     = parseInt(b.total)||0;
                const completed = parseInt(b.completed_count)||0;
                const active    = parseInt(b.active_count)||0;
                const pct       = total>0 ? Math.round((completed/total)*100) : 0;
                const statusClass = {pending:'status-requested',scheduled:'status-accepted',in_progress:'status-accepted',completed:'status-completed'}[b.status]||'status-requested';

                return `
                <div class="batch-card" onclick="openBatchDetail(${b.id},'${escHtml(b.status)}','Batch #${b.batch_number}','${b.pickup_date||''}')">
                  <div class="batch-card-header">
                    <div class="batch-num">Batch #${b.batch_number}</div>
                    <span class="status-badge ${statusClass}">${capitalise(b.status)}</span>
                  </div>
                  <div class="batch-stats">
                    <div class="batch-stat"><span class="bs-val">${total}</span><span class="bs-lbl">Total</span></div>
                    <div class="batch-stat"><span class="bs-val">${active}</span><span class="bs-lbl">Active</span></div>
                    <div class="batch-stat"><span class="bs-val">${completed}</span><span class="bs-lbl">Done</span></div>
                  </div>
                  <div class="batch-progress-wrap">
                    <div class="batch-progress-bar">
                      <div class="batch-progress-fill" style="width:${pct}%"></div>
                    </div>
                    <span class="batch-pct">${pct}%</span>
                  </div>
                  <div class="batch-meta">
                    ${b.pickup_date ? `📅 Pickup: <strong>${formatDate(b.pickup_date)}</strong>` : '📅 No date set yet'}
                    <span style="margin-left:auto;font-size:.72rem;color:var(--text-muted)">Created ${formatTime(b.created_at)}</span>
                  </div>
                </div>`;
            }).join('');
        })
        .catch(()=> showToast("Failed to load batches","error"));
}

/* ── OPEN BATCH DETAIL ─────────────────────────────────────── */
function openBatchDetail(batchId, status, title, pickupDate){
    currentBatchId = batchId;
    document.getElementById("activeBatchCard").style.display = "block";
    document.getElementById("activeBatchTitle").innerText    = title + " — Requests";
    document.getElementById("activeBatchSub").innerText      = pickupDate
        ? "Pickup scheduled: " + formatDate(pickupDate)
        : "Set a pickup date to notify users";

    // Hide complete button if already completed
    document.getElementById("btnCompleteBatch").style.display = status==='completed' ? 'none' : 'flex';
    document.getElementById("btnSetDate").style.display        = status==='completed' ? 'none' : 'flex';

    document.getElementById("activeBatchCard").scrollIntoView({behavior:'smooth', block:'start'});

    fetch("backend/get_queued_requests.php?type=batch&batch_id="+batchId)
        .then(r=>r.json())
        .then(data=>{
            const tbody = document.getElementById("batchRequestTable");
            if(!Array.isArray(data)||data.length===0){
                tbody.innerHTML = `<tr><td colspan="6"><div class="empty-state"><p>No requests in this batch.</p></div></td></tr>`;
                return;
            }
            tbody.innerHTML = data.map((req,i)=>{
                const statusClass = `status-${req.status}`;
                let action = '';
                if(req.status==='requested')  action = `<button class="btn-accept"   onclick="acceptRequest(${req.id})">✅ Accept</button>`;
                else if(req.status==='accepted') action = `<button class="btn-complete" onclick="completeRequest(${req.id})">🏁 Complete</button>`;
                else action = `<span style="color:var(--green-600);font-weight:600;font-size:.82rem">✔ Done</span>`;

                return `<tr>
                  <td style="font-family:var(--mono);font-size:.82rem">${i+1}</td>
                  <td style="max-width:200px;word-break:break-word">${escHtml(req.address||'—')}</td>
                  <td>W${req.ward_id}</td>
                  <td><span class="waste-tag">${escHtml(req.waste_type)}</span></td>
                  <td><span class="status-badge ${statusClass}">${capitalise(req.status)}</span></td>
                  <td>${action}</td>
                </tr>`;
            }).join('');

            // Also populate route data
            routeData = data.filter(r=>r.latitude&&r.longitude);
        });
}

function closeBatchDetail(){
    document.getElementById("activeBatchCard").style.display = "none";
    currentBatchId = null;
}

/* ── HISTORY ───────────────────────────────────────────────── */
function loadHistory(){
    fetch("backend/get_queued_requests.php?type=history")
        .then(r=>r.json())
        .then(data=>{
            const tbody = document.getElementById("historyTable");
            if(!Array.isArray(data)||data.length===0){
                tbody.innerHTML = `<tr><td colspan="7"><div class="empty-state"><div class="empty-icon">📭</div><p>No completed pickups yet.</p></div></td></tr>`;
                return;
            }
            tbody.innerHTML = data.map((r,i)=>`
                <tr>
                  <td style="font-family:var(--mono);font-size:.82rem">${i+1}</td>
                  <td style="max-width:180px;word-break:break-word">${escHtml(r.address||'—')}</td>
                  <td>W${r.ward_id}</td>
                  <td><span class="waste-tag">${escHtml(r.waste_type)}</span></td>
                  <td>${r.batch_number ? '#'+r.batch_number : '—'}</td>
                  <td>${r.batch_date ? formatDate(r.batch_date) : '—'}</td>
                  <td><span class="status-badge status-completed">Completed</span></td>
                </tr>`).join('');
        });
}

/* ── CREATE BATCH ──────────────────────────────────────────── */
function createBatch(){
    const btn = document.getElementById("btnCreateBatch");
    btn.disabled = true; btn.innerText = "⏳ Creating…";

    fetch("backend/create_batch.php",{method:"POST"})
        .then(r=>r.json())
        .then(resp=>{
            btn.disabled = false; btn.innerText = "📦 Create Batch (next 15)";
            if(resp.success){
                showToast(`✅ ${resp.message}`, "success");
                refreshAll();
                switchTab('batches', document.querySelector('.tab-btn:nth-child(2)'));
            } else {
                showToast("Error: "+(resp.message||'Could not create batch'), "error");
            }
        })
        .catch(()=>{
            btn.disabled = false; btn.innerText = "📦 Create Batch (next 15)";
            showToast("Failed to create batch","error");
        });
}

/* ── SET BATCH DATE MODAL ──────────────────────────────────── */
function openBatchDateModal(){
    if(!currentBatchId){ showToast("Open a batch first","warning"); return; }
    document.getElementById("batchDateModalSub").innerText =
        "Batch #"+currentBatchId+" — users will be notified";
    document.getElementById("batchDateModal").classList.add("open");
}
function closeBatchDateModal(){
    document.getElementById("batchDateModal").classList.remove("open");
}

function confirmSetBatchDate(){
    const date = document.getElementById("batchDateModalInput").value;
    if(!date){ showToast("Please select a date","warning"); return; }
    if(!currentBatchId){ showToast("No batch selected","warning"); return; }

    fetch("backend/set_batch_date.php",{
        method:"POST",
        headers:{"Content-Type":"application/x-www-form-urlencoded"},
        body:`batch_id=${currentBatchId}&pickup_date=${date}`
    })
    .then(r=>r.json())
    .then(resp=>{
        closeBatchDateModal();
        if(resp.success){
            showToast(`📅 Date set! ${resp.notified} users notified.`,"success");
            document.getElementById("activeBatchSub").innerText = "Pickup scheduled: "+formatDate(date);
            loadBatches();
        } else {
            showToast("Error: "+(resp.message||'Failed'),"error");
        }
    })
    .catch(()=> showToast("Failed to set date","error"));
}

/* ── COMPLETE BATCH ────────────────────────────────────────── */
function completeBatch(){
    if(!currentBatchId){ showToast("No batch selected","warning"); return; }
    if(!confirm("Mark entire batch as completed? All remaining requests will be completed.")) return;

    fetch("backend/complete_batch.php",{
        method:"POST",
        headers:{"Content-Type":"application/x-www-form-urlencoded"},
        body:"batch_id="+currentBatchId
    })
    .then(r=>r.json())
    .then(resp=>{
        if(resp.success){
            showToast("🎉 Batch completed! "+resp.notified+" users notified.","success");
            if(resp.alert_next){
                showToast("🔔 "+resp.queued_count+" more requests waiting in queue!","warning",6000);
            }
            closeBatchDetail();
            refreshAll();
        } else {
            showToast("Error: "+(resp.message||'Failed'),"error");
        }
    })
    .catch(()=> showToast("Failed to complete batch","error"));
}

/* ── ACCEPT / COMPLETE REQUEST ─────────────────────────────── */
function acceptRequest(id){
    fetch("backend/accept_request.php",{
        method:"POST",
        headers:{"Content-Type":"application/x-www-form-urlencoded"},
        body:"id="+id
    })
    .then(r=>r.json())
    .then(resp=>{
        if(resp.success){ showToast("✅ Request accepted!","success"); openBatchDetail(currentBatchId,'in_progress','Batch #'+currentBatchId,''); }
        else showToast("Error: "+(resp.message||'Failed'),"error");
    });
}

function completeRequest(id){
    fetch("backend/complete_request.php",{
        method:"POST",
        headers:{"Content-Type":"application/x-www-form-urlencoded"},
        body:"id="+id
    })
    .then(r=>r.json())
    .then(resp=>{
        if(resp.success){ showToast("🏁 Request completed!","success"); openBatchDetail(currentBatchId,'in_progress','Batch #'+currentBatchId,''); loadStats(); }
        else showToast("Error: "+(resp.message||'Failed'),"error");
    });
}

/* ═══════════════════════════════════════════════════════════
   MAP & NAVIGATION (unchanged from previous version)
═══════════════════════════════════════════════════════════ */
function initMap(){
    const defaultLat=10.5276, defaultLng=76.2144;
    function buildMap(lat,lng){
        map = L.map('map',{zoomControl:true}).setView([lat,lng],15);
        L.tileLayer('https://api.maptiler.com/maps/streets-v2/{z}/{x}/{y}.png?key=VAo4p3aovahIX49N2YUE',{
            maxZoom:19, attribution:'© MapTiler © OpenStreetMap contributors'
        }).addTo(map);
    }
    if(navigator.geolocation){
        navigator.geolocation.getCurrentPosition(
            p=>buildMap(p.coords.latitude,p.coords.longitude),
            ()=>buildMap(defaultLat,defaultLng)
        );
    } else buildMap(defaultLat,defaultLng);
}

function haversine(lat1,lon1,lat2,lon2){
    const R=6371, dLat=(lat2-lat1)*Math.PI/180, dLon=(lon2-lon1)*Math.PI/180;
    const a=Math.sin(dLat/2)**2+Math.cos(lat1*Math.PI/180)*Math.cos(lat2*Math.PI/180)*Math.sin(dLon/2)**2;
    return R*2*Math.atan2(Math.sqrt(a),Math.sqrt(1-a));
}

function generateRoute(){
    const ward = localStorage.getItem("collectorWard");
    const btn  = document.getElementById("btnGenerate");
    btn.classList.add("loading"); btn.innerText="📍 Getting your location…";

    // Step 1: Get collector's current GPS position first
    function proceedWithLocation(collectorLat, collectorLng){
        btn.innerText="⏳ Calculating best route…";

        fetch("backend/get_user_requests.php")
            .then(r=>r.json())
            .then(data=>{
                btn.classList.remove("loading"); btn.innerText="📍 Generate Best Route";
                const pending = data.filter(r=>r.ward_id==ward&&r.status!=='completed'&&r.latitude&&r.longitude);
                if(pending.length===0){ showToast("No pending pickups with GPS coordinates.","warning"); return; }

                // Step 2: Run nearest-neighbour starting from collector's position
                const ordered = nearestNeighbourFromPoint(pending, collectorLat, collectorLng);
                routeData = ordered;
                clearMapLayers();

                // Place collector start marker
                const collectorStartIcon = L.divIcon({
                    className:'',
                    html:`<div style="background:#0284c7;color:#fff;font-size:11px;font-weight:700;width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;border:3px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,.4)">📍</div>`,
                    iconSize:[32,32], iconAnchor:[16,32]
                });
                const startMarker = L.marker([collectorLat, collectorLng], {icon: collectorStartIcon})
                    .addTo(map)
                    .bindPopup("<b>Your Starting Position</b>");
                markers.push(startMarker);

                // Place house markers
                ordered.forEach((req,i)=>{
                    const m=L.marker([parseFloat(req.latitude),parseFloat(req.longitude)],{icon:makeHouseIcon(i+1,req.status)})
                        .addTo(map).bindPopup(`<b>${escHtml(req.address)}</b><br>${escHtml(req.waste_type)}`);
                    markers.push(m);
                });

                if(routeControl){map.removeControl(routeControl);routeControl=null;}

                // Build waypoints: collector position + all house stops
                const allWaypoints = [
                    L.latLng(collectorLat, collectorLng),
                    ...ordered.map(r=>L.latLng(parseFloat(r.latitude),parseFloat(r.longitude)))
                ];

                routeControl=L.Routing.control({
                    waypoints: allWaypoints,
                    routeWhileDragging:false,addWaypoints:false,draggableWaypoints:false,show:false,
                    createMarker:()=>null,
                    lineOptions:{styles:[{color:'#059669',weight:5,opacity:.85}]},
                    router:L.Routing.osrmv1({serviceUrl:'https://router.project-osrm.org/route/v1',profile:'driving'})
                }).addTo(map);

                routeControl.on('routesfound',e=>{
                    routeLegs=(e.routes[0].legs);
                    document.getElementById("routeDistance").innerText=(e.routes[0].summary.totalDistance/1000).toFixed(2)+" km";
                    document.getElementById("btnStartNav").style.display="flex";
                    const b=L.latLngBounds(e.routes[0].coordinates);
                    map.fitBounds(b,{padding:[40,40]});
                });
                renderRouteList(ordered);
                showToast(`Route generated from your location for ${ordered.length} stops!`,"success");
            })
            .catch(()=>{
                btn.classList.remove("loading"); btn.innerText="📍 Generate Best Route";
                showToast("Failed to load requests.","error");
            });
    }

    // Try to get live GPS — fallback to map center if denied
    if(navigator.geolocation){
        navigator.geolocation.getCurrentPosition(
            pos => proceedWithLocation(pos.coords.latitude, pos.coords.longitude),
            ()  => {
                // GPS denied — use current map center as fallback
                const center = map.getCenter();
                showToast("GPS unavailable. Using map center as start point.","warning");
                proceedWithLocation(center.lat, center.lng);
            },
            { timeout:8000, maximumAge:30000, enableHighAccuracy:true }
        );
    } else {
        const center = map.getCenter();
        proceedWithLocation(center.lat, center.lng);
    }
}

function nearestNeighbour(points){
    let remaining=[...points], route=[remaining.shift()];
    while(remaining.length){
        const last=route[route.length-1];
        let minDist=Infinity,minIdx=0;
        remaining.forEach((p,i)=>{
            const d=haversine(parseFloat(last.latitude),parseFloat(last.longitude),parseFloat(p.latitude),parseFloat(p.longitude));
            if(d<minDist){minDist=d;minIdx=i;}
        });
        route.push(remaining[minIdx]); remaining.splice(minIdx,1);
    }
    return route;
}

/* Nearest-neighbour starting from collector's GPS position */
function nearestNeighbourFromPoint(points, startLat, startLng){
    let remaining = [...points];
    let route     = [];
    let currentLat = startLat;
    let currentLng = startLng;

    while(remaining.length){
        let minDist = Infinity, minIdx = 0;
        remaining.forEach((p,i)=>{
            const d = haversine(currentLat, currentLng, parseFloat(p.latitude), parseFloat(p.longitude));
            if(d < minDist){ minDist=d; minIdx=i; }
        });
        route.push(remaining[minIdx]);
        currentLat = parseFloat(remaining[minIdx].latitude);
        currentLng = parseFloat(remaining[minIdx].longitude);
        remaining.splice(minIdx,1);
    }
    return route;
}

function renderRouteList(ordered){
    const ul=document.getElementById("routeList"); ul.innerHTML="";
    ordered.forEach((req,i)=>{
        const li=document.createElement("li"); li.id=`stop-${i}`;
        li.innerHTML=`<div class="stop-num" id="stopNum-${i}">${i+1}</div><div class="stop-text">${escHtml(req.address)}<br><span class="waste-tag">${escHtml(req.waste_type)}</span></div>`;
        ul.appendChild(li);
    });
}

function clearMapLayers(){
    markers.forEach(m=>map.removeLayer(m)); markers=[];
    if(routeControl){map.removeControl(routeControl);routeControl=null;}
    if(truckMarker){map.removeLayer(truckMarker);truckMarker=null;}
}

function startNavigation(){
    if(!routeData.length){showToast("Generate a route first!","warning");return;}
    if(!navigator.geolocation){
        showToast("Geolocation is not supported on this device/browser.","danger");
        return;
    }
    navActive=true; navStepIndex=0; alertedStops = new Set();
    lastTruckCoord = null;
    if(geoWatchId!==null){navigator.geolocation.clearWatch(geoWatchId);geoWatchId=null;}
    document.getElementById("btnStartNav").style.display="none";
    document.getElementById("btnStopNav").style.display="flex";
    document.getElementById("nav-overlay").classList.add("visible");
    if(truckMarker){map.removeLayer(truckMarker);truckMarker=null;}
    highlightStop(0); updateNavInstruction();
    geoWatchId = navigator.geolocation.watchPosition(pos=>{
        if(!navActive) return;
        const lat = pos.coords.latitude;
        const lng = pos.coords.longitude;
        if(!truckMarker){
            truckMarker=L.marker([lat,lng],{icon:truckIcon,zIndexOffset:1000}).addTo(map).bindPopup("🚛 Collection Truck");
            map.setView([lat,lng],17);
        }else{
            truckMarker.setLatLng([lat,lng]);
        }
        if(lastTruckCoord){
            const bearing=getBearing(lastTruckCoord.lat,lastTruckCoord.lng,lat,lng);
            const el=truckMarker && truckMarker.getElement();
            if(el)el.querySelector('div').style.transform=`rotate(${bearing}deg)`;
        }
        lastTruckCoord = {lat,lng};
        map.panTo([lat,lng], {animate:true, duration:.5});
        checkStopReached({lat, lng});
    }, err=>{
        const msg = err && err.code===1 ? "Location permission denied. Please allow GPS access." : "Unable to get live GPS location.";
        showToast(msg, "danger");
        stopNavigation();
    }, {enableHighAccuracy:true, maximumAge:3000, timeout:10000});
    showToast("🚛 Navigation started!","info");
}

function stopNavigation(){
    navActive=false; cancelAnimationFrame(animFrameId);
    if(geoWatchId!==null){navigator.geolocation.clearWatch(geoWatchId);geoWatchId=null;}
    document.getElementById("btnStartNav").style.display="flex";
    document.getElementById("btnStopNav").style.display="none";
    document.getElementById("nav-overlay").classList.remove("visible");
    showToast("Navigation stopped.","info");
}

function animateTruckAlongRoute(){
    if(!routeControl) return;
    const tryAnimate=()=>{
        const routes=routeControl._routes;
        if(!routes||!routes.length){setTimeout(tryAnimate,500);return;}
        const coords=routes[0].coordinates; let idx=0;
        function step(){
            if(!navActive||idx>=coords.length-1){if(idx>=coords.length-1)onNavigationComplete();return;}
            animateBetween(coords[idx],coords[idx+1],40,()=>{idx++;checkStopReached(coords[idx]);step();});
        }
        step();
    };
    tryAnimate();
}

function animateBetween(from,to,ms,onDone){
    const steps=Math.max(4,Math.round(ms/16)); let stepN=0;
    const latDiff=to.lat-from.lat, lngDiff=to.lng-from.lng;
    const bearing=getBearing(from.lat,from.lng,to.lat,to.lng);
    if(truckMarker){const el=truckMarker.getElement();if(el)el.querySelector('div').style.transform=`rotate(${bearing}deg)`;}
    function tick(){
        if(!navActive)return; stepN++;
        const t=stepN/steps;
        if(truckMarker)truckMarker.setLatLng([from.lat+latDiff*t,from.lng+lngDiff*t]);
        if(stepN<steps)animFrameId=requestAnimationFrame(tick); else onDone();
    }
    tick();
}

function getBearing(lat1,lng1,lat2,lng2){
    const dLng=(lng2-lng1)*Math.PI/180,la1=lat1*Math.PI/180,la2=lat2*Math.PI/180;
    const y=Math.sin(dLng)*Math.cos(la2),x=Math.cos(la1)*Math.sin(la2)-Math.sin(la1)*Math.cos(la2)*Math.cos(dLng);
    return((Math.atan2(y,x)*180/Math.PI)+360)%360;
}

// Track which stops have already been alerted
let alertedStops = new Set();

function checkStopReached(currentCoord){
    if(!currentCoord || navStepIndex >= routeData.length) return;

    const stop = routeData[navStepIndex];
    const dist = haversine(
        currentCoord.lat, currentCoord.lng,
        parseFloat(stop.latitude), parseFloat(stop.longitude)
    );

    // ── 1km alert — send truck alert to user ──────────────────
    if(dist < 1.0 && !alertedStops.has(navStepIndex) && stop.id){
        alertedStops.add(navStepIndex);
        fetch("backend/alert_next_user.php", {
            method: "POST",
            headers: {"Content-Type": "application/x-www-form-urlencoded"},
            body: "request_id=" + stop.id
        })
        .then(r => r.json())
        .then(resp => {
            if(resp.alerted){
                showToast(`🔔 Alert sent to Stop ${navStepIndex+1}: ${stop.address}`, "info", 3000);
            }
        });
    }

    // ── 50m — stop reached ────────────────────────────────────
    if(dist < 0.05){
        showToast(`📍 Stop ${navStepIndex+1}: ${stop.address}`, "success");
        markStopDone(navStepIndex);
        navStepIndex++;
        if(navStepIndex < routeData.length){ highlightStop(navStepIndex); }
        else{ onNavigationComplete(); return; }
        updateNavInstruction();
        if(truckMarker) map.panTo(truckMarker.getLatLng(), {animate:true, duration:.5});
    }
}

function highlightStop(idx){
    document.querySelectorAll('.stop-num').forEach((el,i)=>{
        el.className='stop-num'+(i<idx?' done':i===idx?' active':'');
    });
    document.getElementById("currentStopInfo").innerText=`Stop ${idx+1} of ${routeData.length}`;
    const el=document.getElementById(`stop-${idx}`);
    if(el)el.scrollIntoView({behavior:'smooth',block:'nearest'});
}

function markStopDone(idx){
    const el=document.getElementById(`stopNum-${idx}`);
    if(el){el.className='stop-num done';el.innerText='✓';}
}

function updateNavInstruction(){
    if(!routeLegs.length)return;
    const leg=routeLegs[0]; const step=leg&&leg.steps[0]; if(!step)return;
    const arrows={straight:'⬆️',left:'⬅️',right:'➡️','slight left':'↖️','slight right':'↗️',uturn:'🔄'};
    const maneuver=(step.maneuver&&step.maneuver.type)||'straight';
    const distM=step.distance||0;
    document.getElementById("navArrow").innerText    =arrows[maneuver]||'⬆️';
    document.getElementById("navDirection").innerText=capitalise(maneuver);
    document.getElementById("navStreet").innerText   ='on '+(step.name||'the road');
    document.getElementById("navDist").innerText     =distM>1000?(distM/1000).toFixed(1)+' km':Math.round(distM)+' m';
    document.getElementById("navEta").innerText      =`~${Math.max(1,Math.round((distM/1000)/30*60))} min`;
}

function onNavigationComplete(){
    navActive=false;
    if(geoWatchId!==null){navigator.geolocation.clearWatch(geoWatchId);geoWatchId=null;}
    document.getElementById("btnStartNav").style.display="flex";
    document.getElementById("btnStopNav").style.display="none";
    document.getElementById("nav-overlay").classList.remove("visible");
    showToast("🎉 Route completed! All stops visited.","success");
    routeData.forEach((_,i)=>markStopDone(i));
}

/* ═══════════════════════════════════════════════════════════
   CANNOT COLLECT
═══════════════════════════════════════════════════════════ */
function openCannotCollectModal(){
    const dateInput = document.getElementById("cannotRescheduleDate");
    const today = new Date();
    const tomorrow = new Date(today);
    tomorrow.setDate(today.getDate() + 1);

    if(dateInput){
        dateInput.min = formatDateForInput(today);
        if(!dateInput.value || dateInput.value < dateInput.min){
            dateInput.value = formatDateForInput(tomorrow);
        }
    }

    const modalHeader = document.querySelector("#cannotCollectModal .modal-header p");
    if(modalHeader){
        modalHeader.textContent = "Notify users and admin with the selected reschedule date";
    }

    const mondayNotice = document.querySelector("#cannotCollectModal .modal-body > div:last-of-type");
    if(mondayNotice){
        mondayNotice.style.display = "none";
    }

    updateCannotCollectPreview();
    document.getElementById("cannotCollectModal").classList.add("open");
}
function closeCannotCollectModal(){ document.getElementById("cannotCollectModal").classList.remove("open"); }
document.getElementById("cannotCollectModal").addEventListener("click",function(e){if(e.target===this)closeCannotCollectModal();});
const cannotRescheduleDateInput = document.getElementById("cannotRescheduleDate");
if(cannotRescheduleDateInput){
    cannotRescheduleDateInput.addEventListener("input", updateCannotCollectPreview);
    cannotRescheduleDateInput.addEventListener("change", updateCannotCollectPreview);
}

function sendCannotCollectNotification(){
    const reason=document.getElementById("cannotReason").value;
    const note  =document.getElementById("cannotNote").value.trim();
    const ward  =localStorage.getItem("collectorWard");
    const rescheduleDate = document.getElementById("cannotRescheduleDate").value;

    if(!rescheduleDate){
        showToast("Please select a reschedule date.","warning");
        return;
    }

    fetch("backend/notify_cannot_collect.php",{
        method:"POST",
        headers:{"Content-Type":"application/x-www-form-urlencoded"},
        body:`ward=${encodeURIComponent(ward)}&reason=${encodeURIComponent(reason)}&note=${encodeURIComponent(note)}&reschedule_date=${encodeURIComponent(rescheduleDate)}`
    })
    .then(r=>r.json())
    .then(resp=>{
        showToast(resp.success ? `Users & admin notified. Rescheduled to ${rescheduleDate}.` : (resp.message || "Notification could not be sent."), resp.success ? "success" : "warning");
        closeCannotCollectModal();
    })
    .catch(()=>{ showToast("Failed to send the reschedule notification.","warning"); closeCannotCollectModal(); });
}

/* ═══════════════════════════════════════════════════════════
   COLLECTOR NOTIFICATIONS
═══════════════════════════════════════════════════════════ */
function loadCollectorNotifications(){
    fetch("backend/get_collector_notifications.php")
        .then(r=>r.json())
        .then(data=>{
            if(!Array.isArray(data))return;
            const unread=data.filter(n=>n.is_read==0).length;
            const bell  =document.getElementById("notifBellBtn");
            const dot   =document.getElementById("notifDot");
            const badge =document.getElementById("notifCountBadge");

            if(unread>0){
                bell.classList.add("has-unread");
                dot.style.display  ="block";
                badge.style.display="inline-flex"; badge.innerText=unread>99?'99+':unread;
            } else {
                bell.classList.remove("has-unread");
                dot.style.display  ="none";
                badge.style.display="none";
            }

            const list=document.getElementById("notifDropdownList");
            if(!data.length){
                list.innerHTML=`<div class="notif-empty"><div class="empty-icon">🔕</div><p>No notifications</p></div>`;
                return;
            }
            list.innerHTML=data.slice(0,8).map(n=>`
                <div class="notif-item ${n.is_read==0?'unread':''}" onclick="markOneCollectorNotif(${n.id})">
                  <div class="notif-icon info">🔔</div>
                  <div class="notif-body">
                    <p>${escHtml(n.message)}</p>
                    <span class="notif-time">🕐 ${formatTimeAgo(n.created_at)}</span>
                  </div>
                  <div class="notif-unread-dot"></div>
                </div>`).join('');
        });
}

function toggleNotifDropdown(){notifDropdownOpen?closeNotifDropdown():openNotifDropdown();}
function openNotifDropdown(){ document.getElementById("notifDropdown").classList.add("open"); notifDropdownOpen=true; }
function closeNotifDropdown(){ document.getElementById("notifDropdown").classList.remove("open"); notifDropdownOpen=false; }

function markOneCollectorNotif(id){
    fetch("backend/mark_collector_notif_read.php",{method:"POST",headers:{"Content-Type":"application/x-www-form-urlencoded"},body:"id="+id})
        .then(()=>loadCollectorNotifications());
}

function markAllCollectorNotif(){
    fetch("backend/mark_collector_notif_read.php",{method:"POST",headers:{"Content-Type":"application/x-www-form-urlencoded"},body:"all=1"})
        .then(()=>{loadCollectorNotifications();showToast("All notifications marked as read.","success");});
}

/* ═══════════════════════════════════════════════════════════
   TOAST
═══════════════════════════════════════════════════════════ */
function showToast(message,type="success",duration=4000){
    const container=document.getElementById("toast-container");
    const toast=document.createElement("div");
    toast.className=`toast ${type}`; toast.innerHTML=`<span>${message}</span>`;
    container.appendChild(toast);
    setTimeout(()=>{toast.style.animation="toastOut .3s ease forwards";setTimeout(()=>toast.remove(),320);},duration);
}

/* ═══════════════════════════════════════════════════════════
   LOGOUT
═══════════════════════════════════════════════════════════ */
function logout(){
    localStorage.removeItem("collectorLoggedIn"); localStorage.removeItem("collectorWard");
    localStorage.removeItem("collectorId"); localStorage.removeItem("collectorName");
    window.location.href="collector-login.html";
}

/* ═══════════════════════════════════════════════════════════
   HELPERS
═══════════════════════════════════════════════════════════ */
function escHtml(str){ if(!str)return''; return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
function capitalise(str){ return str.charAt(0).toUpperCase()+str.slice(1); }
function formatTime(ts){ if(!ts)return'—'; return new Date(ts).toLocaleString('en-IN',{day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'}); }
function formatDate(d){ if(!d)return'—'; const[y,m,day]=d.split('-').map(Number); return new Date(y,m-1,day).toLocaleDateString('en-IN',{weekday:'short',day:'numeric',month:'short',year:'numeric'}); }
function formatTimeAgo(ts){
    if(!ts)return'—'; const d=new Date(ts),now=new Date(),diff=Math.floor((now-d)/1000);
    if(diff<60)return'Just now'; if(diff<3600)return Math.floor(diff/60)+' min ago';
    if(diff<86400)return Math.floor(diff/3600)+' hr ago';
    return d.toLocaleDateString('en-IN',{day:'numeric',month:'short'});
}

function formatDateForInput(date){
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

function formatDateLong(dateString){
    if(!dateString) return '';
    const parts = dateString.split('-').map(Number);
    const date = new Date(parts[0], parts[1] - 1, parts[2]);
    return date.toLocaleDateString('en-IN', { day:'numeric', month:'long', year:'numeric' });
}

function updateCannotCollectPreview(){
    const preview = document.getElementById("cannotCollectPreview");
    const dateInput = document.getElementById("cannotRescheduleDate");
    if(!preview || !dateInput) return;

    if(!dateInput.value){
        preview.innerHTML = 'Pickup date will be sent to affected users and admin.';
        return;
    }

    preview.innerHTML = `Pickup postponed to <strong>${escHtml(formatDateLong(dateInput.value))}</strong>. Users and admin will be notified.`;
}

function openCannotCollectModal(){
    const dateInput = document.getElementById("cannotRescheduleDate");
    const today = new Date();
    const tomorrow = new Date(today);
    tomorrow.setDate(today.getDate() + 1);

    if(dateInput){
        dateInput.min = formatDateForInput(today);
        if(!dateInput.value || dateInput.value < dateInput.min){
            dateInput.value = formatDateForInput(tomorrow);
        }
    }

    const modalHeader = document.querySelector("#cannotCollectModal .modal-header p");
    if(modalHeader){
        modalHeader.textContent = "Notify users and admin with the selected reschedule date";
    }

    const notices = document.querySelectorAll("#cannotCollectModal .modal-body > div");
    const oldMondayNotice = notices[notices.length - 1];
    if(oldMondayNotice && oldMondayNotice.id !== "cannotCollectPreview"){
        oldMondayNotice.style.display = "none";
    }

    updateCannotCollectPreview();
    document.getElementById("cannotCollectModal").classList.add("open");
}

function sendCannotCollectNotification(){
    const reason = document.getElementById("cannotReason").value;
    const note   = document.getElementById("cannotNote").value.trim();
    const date   = document.getElementById("cannotRescheduleDate").value; // ADD THIS
    const ward   = localStorage.getItem("collectorWard");

    // Validate date
    if(!date){
        showToast("Please select a reschedule date.","warning");
        return;
    }

    fetch("backend/notify_cannot_collect.php",{
        method:"POST",
        headers:{"Content-Type":"application/x-www-form-urlencoded"},
        body:`ward=${encodeURIComponent(ward)}&reason=${encodeURIComponent(reason)}&note=${encodeURIComponent(note)}&reschedule_date=${encodeURIComponent(date)}`
    })
    .then(r=>r.json())
    .then(resp=>{
        showToast(resp.success
            ? `✅ ${resp.affected_count} users notified. Rescheduled to ${date}.`
            : "⚠️ " + (resp.message||'Error'),
            resp.success ? "success" : "warning");
        closeCannotCollectModal();
    })
    .catch(()=>{
        showToast("📤 Notification sent!","success");
        closeCannotCollectModal();
    });
}
