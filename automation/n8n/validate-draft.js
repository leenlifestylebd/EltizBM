const p = $('Prepare draft request').first().json;
let result = {decision:'handover',draft:'',reason:'AI unavailable or returned an invalid answer.',confidence:0};
try {
 const content = $input.first().json.choices?.[0]?.message?.content;
 const data = JSON.parse(content);
 if (['review','handover'].includes(data.decision) && typeof data.draft==='string' && typeof data.reason==='string' && data.reason.trim() && typeof data.confidence==='number' && Number.isFinite(data.confidence) && data.confidence>=0 && data.confidence<=1) {
   result={decision:data.decision,draft:data.draft.slice(0,2000),reason:data.reason.slice(0,500),confidence:data.confidence};
   if (!p.product_verified || data.confidence<0.85 || !data.draft.trim()) {
     result.decision='handover';
     if (!p.product_verified) result.reason='Product context needs human confirmation. '+result.reason;
   }
 }
} catch (_) {}
result.reason=result.reason.slice(0,500);
return [{json:{event_id:p.event_id,lease_token:p.lease_token,...result}}];
