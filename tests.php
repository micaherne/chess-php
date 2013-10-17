<?php

require_once 'prog.php';

class ChessTest extends PHPUnit_Framework_TestCase {
	
	public function setUp() {
		$this->engine = new tree();
	}
	
	public function testStartpos() {
		$e = $this->engine;
		$this->engine->sp();
		$this->assertEquals(2, $e->b[1]);
		$this->assertTrue($e->wtm);
	}
	
	public function testFen() {
		$e = $this->engine;
		$e->fen('r3k2r/Pppp1ppp/1b3nbN/nP6/BBP1P3/q4N2/Pp1P2PP/R2Q1RK1 w kq - 0 1');
		$this->assertEquals(-tree::R, $e->b[0x70]);
		$this->assertEquals(tree::P, $e->b[0x60]);
		$this->assertEquals(0, $e->hm);
		$this->assertEquals(1, $e->fm);
	}
	
	public function testMove() {
		$e = $this->engine;
		$e->sp();
		$e->m(array(0x14, 0x34, null));
		$this->assertEquals(1, $e->b[0x34]);
		$this->assertFalse($e->wtm);
	}
	
	public function testMoveCheckTest() {
		$e = $this->engine;
		$e->fen('r3k2r/Pppp1ppp/1b3nbN/nP6/BBP1P3/q4N2/Pp1P2PP/R2Q1RK1 w kq - 0 1');
		
		$m1 = $e->m(array(0x13, 0x23, null));
		//$this->assertFalse($m1);
	}
	
	public function testUndo() {
		$e = $this->engine;
		$e->sp();
		$e->m(array(0x14, 0x34, null));
		$e->u();
		$this->assertEquals(2, $e->b[1]);
		$this->assertTrue($e->wtm);
	}
	
	public function testCastling() {
		$e = $this->engine;
		
	}
	
	public function testGeneratePseudoLegalMoves() {
		$e = $this->engine;
		$e->sp();
		$m = $e->gplm(true);
		$this->assertCount(20, $m);
		$e->m(array(0x14, 0x34, null));
		$m = $e->gplm(false);
		$this->assertCount(20, $m);
		
		$e->fen('r3k2r/Pppp1ppp/1b3nbN/nP6/BBP1P3/q4N2/Pp1P2PP/R2Q1RK1 w kq - 0 1');
		$m = $e->gplm(true);
		$this->assertCount(32, $m); // TODO: This may not be the correct number
	}
	
	public function testLegalMoves() {
		$e = $this->engine;

		$e->fen('r3k2r/Pppp1ppp/1b3nbN/nP6/BBP1P3/q4N2/Pp1P2PP/R2Q1RK1 w kq - 0 1');
		$m = $e->gplm(true);
		$i = 0;
		foreach ($m as $mv) {
			if ($e->m($mv)) {
				$i++;
				$e->u();
			}
		}
		$this->assertEquals(6, $i);
	}
	
}