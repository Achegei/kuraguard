'use strict';
const test = require('node:test');
const assert = require('node:assert/strict');
const {GeographicAssignmentEngine: Engine, keyOf} = require('./kuraguard_hierarchical_engine.cjs');
function fixture() {
 const e = new Engine({scopeVoters: 150});
 const county = e.addArea({level:'county',id:1,registeredVoters:100});
 const constituency = e.addArea({level:'constituency',id:2,parentKey:county,registeredVoters:40});
 const ward = e.addArea({level:'ward',id:3,parentKey:constituency,registeredVoters:10});
 const centre = e.addArea({level:'polling_centre',id:4,parentKey:ward,registeredVoters:3});
 return {e,county,constituency,ward,centre};
}
test('county allocation with nested overrides reconciles exactly',()=>{
 const {e,county,constituency,ward,centre}=fixture();
 e.assign(county,1);e.assign(constituency,2);e.assign(ward,1);e.assign(centre,2);
 assert.deepEqual(e.totals().candidates,{'1':67,'2':33});
 assert.equal(e.totals().unassigned,50);
 assert.equal(e.totals().assigned+e.totals().unassigned,150);
 assert.equal(e.ownerOf(centre),2);
 assert.equal(e.ownerOf(ward),1);
});
test('segment totals inherit ancestors and do not include other counties',()=>{
 const {e,county,constituency,ward}=fixture();e.assign(county,1);e.assign(ward,2);
 assert.deepEqual(e.totals({rootKey:constituency}).candidates,{'1':30,'2':10});
 assert.equal(e.totals({rootKey:constituency}).denominator,40);
});
test('removing a candidate restores inherited parent assignment',()=>{
 const {e,county,constituency}=fixture();e.assign(county,1);e.assign(constituency,2);e.removeCandidate(2);
 assert.equal(e.ownerOf(constituency),1);
 assert.deepEqual(e.totals().candidates,{'1':100});
});
test('MCA root-level polling centres remain supported',()=>{
 const e = new Engine({scopeVoters:12,rootLevel:'polling_centre'});
 const a=e.addArea({level:'polling_centre',id:9,registeredVoters:5});
 e.assign(a,1);assert.equal(e.totals().assigned,5);assert.equal(e.totals().unassigned,7);
});
test('duplicate IDs across geographic levels do not collide',()=>{
 const e=new Engine({scopeVoters:20});const a=e.addArea({level:'county',id:1,registeredVoters:20});
 const b=e.addArea({level:'constituency',id:1,parentKey:a,registeredVoters:7});
 assert.notEqual(a,b);e.assign(a,1);e.assign(b,2);
 assert.deepEqual(e.totals().candidates,{'1':13,'2':7});
});
test('reject invalid hierarchy and inflated child aggregates',()=>{
 const e=new Engine({scopeVoters:10});const a=e.addArea({level:'county',id:1,registeredVoters:10});
 assert.throws(()=>e.addArea({level:'ward',id:2,parentKey:a,registeredVoters:1}),/parent/);
 e.addArea({level:'constituency',id:2,parentKey:a,registeredVoters:11});
 assert.throws(()=>e.totals(),/exceed/);
});
test('reassignment is exclusive and can be reversed',()=>{
 const {e,county}=fixture();e.assign(county,1);e.assign(county,2);
 assert.deepEqual(e.totals().candidates,{'2':100});e.unassign(county);
 assert.equal(e.totals().assigned,0);
});
test('reject inconsistent records and invalid candidate IDs',()=>{
 const {e,county}=fixture();
 assert.throws(()=>e.addArea({level:'county',id:1,registeredVoters:99}),/Conflicting/);
 assert.throws(()=>e.assign(county,0),/candidate/);
 assert.equal(keyOf('county',1),'county:1');
});
