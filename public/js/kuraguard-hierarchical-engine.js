/* KuraGuard C1 — historical-electorate geographic assignment engine.
 * Pure CommonJS module; no browser, network or Laravel dependencies.
 * Inputs are authenticated, campaign-scoped geographic records.
 * Coverage is not votes, support or winning probability.
 */
'use strict';
const LEVELS = ['county', 'constituency', 'ward', 'polling_centre'];
const keyOf = (level, id) => `${level}:${id}`;
function integer(value, label, allowZero = false) {
  if (!Number.isSafeInteger(value) || value < (allowZero ? 0 : 1)) throw new Error(`Invalid ${label}`);
  return value;
}
class GeographicAssignmentEngine {
  constructor({scopeVoters, rootLevel = 'county'} = {}) {
    this.scopeVoters = integer(scopeVoters, 'scope voters', true);
    if (!LEVELS.includes(rootLevel)) throw new Error('Invalid root level');
    this.rootLevel = rootLevel;
    this.nodes = new Map();
    this.assignments = new Map();
  }
  addArea({level, id, parentKey = null, registeredVoters, registerComplete = true}) {
    if (!LEVELS.includes(level) || LEVELS.indexOf(level) < LEVELS.indexOf(this.rootLevel)) throw new Error('Invalid area level');
    integer(id, 'area ID'); integer(registeredVoters, 'registered voters', true);
    const key = keyOf(level, id);
    if (parentKey !== null) {
      const parent = this.nodes.get(parentKey);
      if (!parent || LEVELS.indexOf(parent.level) + 1 !== LEVELS.indexOf(level)) throw new Error('Invalid geographic parent');
    } else if (level !== this.rootLevel) throw new Error('Root area must match root level');
    const previous = this.nodes.get(key);
    if (previous && (previous.parentKey !== parentKey || previous.voters !== registeredVoters || previous.registerComplete !== Boolean(registerComplete))) {
      throw new Error('Conflicting geographic record');
    }
    if (!previous) this.nodes.set(key, {key, level, id, parentKey, voters: registeredVoters, registerComplete: Boolean(registerComplete)});
    return key;
  }
  assign(key, candidateId) {
    if (!this.nodes.has(key)) throw new Error('Unknown geographic area');
    integer(candidateId, 'candidate ID');
    this.assignments.set(key, candidateId);
  }
  unassign(key) { this.assignments.delete(key); }
  removeCandidate(candidateId) {
    for (const [key, id] of this.assignments) if (id === candidateId) this.assignments.delete(key);
  }
  ownerOf(key) {
    let node = this.nodes.get(key);
    if (!node) throw new Error('Unknown geographic area');
    while (node) {
      if (this.assignments.has(node.key)) return this.assignments.get(node.key);
      node = node.parentKey ? this.nodes.get(node.parentKey) : null;
    }
    return null;
  }
  totals({rootKey = null} = {}) {
    const children = new Map();
    for (const node of this.nodes.values()) {
      if (!children.has(node.parentKey)) children.set(node.parentKey, []);
      children.get(node.parentKey).push(node);
    }
    const result = new Map();
    let unassigned = 0;
    const add = (owner, voters) => {
      if (owner === null) unassigned += voters;
      else result.set(owner, (result.get(owner) || 0) + voters);
    };
    const walk = (node, inherited) => {
      const owner = this.assignments.has(node.key) ? this.assignments.get(node.key) : inherited;
      const descendants = children.get(node.key) || [];
      const childVoters = descendants.reduce((sum, child) => sum + child.voters, 0);
      if (childVoters > node.voters) throw new Error(`Child totals exceed parent at ${node.key}`);
      add(owner, node.voters - childVoters);
      for (const child of descendants) walk(child, owner);
    };
    let denominator;
    if (rootKey !== null) {
      const root = this.nodes.get(rootKey);
      if (!root) throw new Error('Unknown segment');
      denominator = root.voters;
      // A segment inherits ownership from its nearest assigned ancestor.
      const inherited = root.parentKey ? this.ownerOf(root.parentKey) : null;
      walk(root, inherited);
    } else {
      const roots = children.get(null) || [];
      const rootSum = roots.reduce((sum, node) => sum + node.voters, 0);
      if (rootSum > this.scopeVoters) throw new Error('Loaded root totals exceed campaign scope');
      denominator = this.scopeVoters;
      add(null, this.scopeVoters - rootSum);
      for (const root of roots) walk(root, null);
    }
    const assigned = [...result.values()].reduce((a,b) => a+b, 0);
    if (assigned + unassigned !== denominator) throw new Error('Coverage reconciliation failed');
    return {denominator, assigned, unassigned, candidates: Object.fromEntries(result), coveragePercent: Object.fromEntries([...result].map(([id,v]) => [id, denominator ? 100*v/denominator : 0])), unassignedPercent: denominator ? 100*unassigned/denominator : 0};
  }
}
window.KuraGuardGeography = {GeographicAssignmentEngine, LEVELS, keyOf};
