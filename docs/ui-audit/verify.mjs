const { chromium } = await import(process.env.PLAYWRIGHT_MODULE ?? '/tmp/contapro-browser/node_modules/playwright/index.mjs');
const { default: AxeBuilder } = await import(process.env.AXE_MODULE ?? '/tmp/contapro-browser/node_modules/@axe-core/playwright/dist/index.mjs');
import { mkdir, writeFile } from 'node:fs/promises';
import assert from 'node:assert/strict';
const output=new URL('./after', import.meta.url).pathname; await mkdir(output,{recursive:true});
const baseURL=process.env.APP_URL ?? 'http://localhost:8000';
const browser=await chromium.launch({args:['--no-sandbox',...(process.env.APP_HOST_IP ? [`--host-resolver-rules=MAP localhost ${process.env.APP_HOST_IP}`] : [])]});
const context=await browser.newContext({viewport:{width:1440,height:900},colorScheme:'light'});
const page=await context.newPage();
const errors=[];page.on('pageerror', e=>errors.push(String(e)));page.on('response',r=>{if(r.status()>=400)errors.push(`${r.status()} ${r.url()}`)});
await page.goto(`${baseURL}/login`,{waitUntil:'networkidle'});
const image=page.locator('img'); assert(await image.evaluate(e=>e.complete&&e.naturalWidth>0),'Auth asset loaded');
await page.getByRole('button',{name:'Preencher conta demo'}).click();await page.getByRole('button',{name:'Entrar',exact:true}).click();await page.waitForURL('**/dashboard');
const responsive=[];
for(const route of ['dashboard','transactions','accounts','portfolios','reports','agenda','expected-incomes','recurring','installments','budgets','goals','assets','brokers','banks','categories','imports','reconciliations','profile']){
 await page.goto(`${baseURL}/${route}`,{waitUntil:'networkidle'});
 for(const width of [1920,1440,1366,1280,1024,768,430,390,360]){
  await page.setViewportSize({width,height:width<=430?844:900});await page.waitForTimeout(250);
  const overflow=await page.evaluate(()=>({width:innerWidth,scroll:document.documentElement.scrollWidth,elements:[...document.querySelectorAll('main *')].filter(e=>e.getBoundingClientRect().right>innerWidth+1).slice(0,5).map(e=>({tag:e.tagName,text:e.textContent?.trim().slice(0,50),class:e.className}))}));
  responsive.push({route,width,overflow:overflow.scroll>width,details:overflow.scroll>width?overflow:undefined});
  if(['dashboard','transactions','accounts','reports'].includes(route)&&[1366,768,360].includes(width)) await page.screenshot({path:`${output}/${route}-${width}.png`,fullPage:true});
 }
}
await page.setViewportSize({width:1440,height:900}); await page.goto(`${baseURL}/dashboard`,{waitUntil:'networkidle'});
const monthlyValues=page.getByText('Ver valores por mês',{exact:true});
assert(await monthlyValues.evaluate(e=>e.getBoundingClientRect().bottom <= e.closest('article').getBoundingClientRect().bottom),'Monthly values stays inside chart card');
await monthlyValues.click();assert(await page.locator('details').getAttribute('open')!==null,'Monthly values opens');await monthlyValues.click();
await page.getByRole('button',{name:'Novo lançamento',exact:true}).click();
const dialog=page.getByRole('dialog'); await dialog.waitFor(); await page.keyboard.press('Tab');
assert(await page.evaluate(()=>document.querySelector('[role="dialog"]')?.contains(document.activeElement)),'Focus starts inside dialog');
await page.keyboard.press('Shift+Tab');assert(await page.evaluate(()=>document.querySelector('[role="dialog"]')?.contains(document.activeElement)),'Focus wraps inside dialog');
await page.keyboard.press('Tab');await page.keyboard.press('Escape');await dialog.waitFor({state:'hidden'});
assert(await page.getByRole('button',{name:'Novo lançamento',exact:true}).evaluate(e=>e===document.activeElement),'Focus restored');
await page.setViewportSize({width:390,height:844});await page.getByRole('button',{name:'Abrir menu',exact:true}).click();await dialog.waitFor();await page.keyboard.press('Escape');await dialog.waitFor({state:'hidden'});assert(await page.getByRole('button',{name:'Abrir menu',exact:true}).evaluate(e=>e===document.activeElement),'Menu restores focus');
const accessibility=[];
for(const route of ['dashboard','transactions','accounts','portfolios','reports','profile']){
 await page.goto(`${baseURL}/${route}`,{waitUntil:'networkidle'});
 const axe=await new AxeBuilder({page}).withTags(['wcag2a','wcag2aa','wcag21aa']).analyze();
 accessibility.push({route,violations:axe.violations.map(v=>({id:v.id,impact:v.impact,description:v.description,nodes:v.nodes.map(n=>({target:n.target,summary:n.failureSummary}))}))});
}
await page.goto(`${baseURL}/dashboard`,{waitUntil:'networkidle'});await page.getByRole('button',{name:'Alternar tema'}).click();await page.setViewportSize({width:1440,height:900});await page.waitForTimeout(300);await page.screenshot({path:`${output}/dashboard-dark-1440.png`,fullPage:true});
const dark=await new AxeBuilder({page}).withTags(['wcag2a','wcag2aa','wcag21aa']).analyze();accessibility.push({route:'dashboard-dark',violations:dark.violations.map(v=>({id:v.id,impact:v.impact,nodes:v.nodes.map(n=>({target:n.target,summary:n.failureSummary}))}))});
await page.goto(`${baseURL}/portfolios`,{waitUntil:'networkidle'});const portfolio=await page.getByRole('link',{name:/Abrir carteira/}).first().getAttribute('href'); if(portfolio){await page.goto(portfolio,{waitUntil:'networkidle'});for(const width of [1440,390]){await page.setViewportSize({width,height:900});await page.waitForTimeout(300);await page.screenshot({path:`${output}/portfolio-detail-${width}.png`,fullPage:true});}}
await writeFile(`${output}/verification.json`,JSON.stringify({responsive,accessibility,errors,keyboard:'Dialog and mobile menu focus containment/Escape/restore passed',asset:'Loaded'},null,2));
console.log(JSON.stringify({overflow:responsive.filter(r=>r.overflow),accessibility,errors,keyboard:'passed',asset:'loaded'},null,2));
await browser.close();

assert.equal(responsive.filter(r=>r.overflow).length,0,'No page overflow');
assert.equal(accessibility.reduce((n,r)=>n+r.violations.length,0),0,'No sampled axe violations');
assert.equal(errors.length,0,'No HTTP/JS errors');
