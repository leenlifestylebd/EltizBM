const p = $('Prepare draft request').first().json;
let result = {decision:'handover',draft:'',reason:'AI unavailable or returned an invalid answer.',confidence:0};
try {
 const response = $input.first().json;
 if (response.error) result.reason = 'AI provider could not complete the request. Please review manually.';
 if (response.choices?.[0]?.finish_reason === 'length') result.reason = 'AI output was truncated. Please review manually.';
 const content = response.choices?.[0]?.message?.content;
 if (typeof content !== 'string' || response.error || response.choices?.[0]?.finish_reason === 'length') throw new Error('Incomplete AI response');
 const cleaned = content.trim().replace(/^```(?:json)?\s*([\s\S]*?)\s*```$/i, '$1');
 const data = JSON.parse(cleaned);
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
